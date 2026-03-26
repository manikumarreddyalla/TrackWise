<?php
declare(strict_types=1);

/**
 * Calculate the next date for a recurring expense based on recurrence type
 *
 * @param string $lastDate The last date in Y-m-d format
 * @param string $recurrenceType The type of recurrence
 * @return string|null The next date in Y-m-d format, or null if calculation fails
 */
function calculateNextDate(string $lastDate, string $recurrenceType): ?string
{
    try {
        $date = DateTime::createFromFormat('Y-m-d', $lastDate);
        if (!$date) {
            return null;
        }

        $interval = match ($recurrenceType) {
            'Daily' => new DateInterval('P1D'),
            'Weekly' => new DateInterval('P7D'),
            'Bi-Weekly' => new DateInterval('P14D'),
            'Monthly' => new DateInterval('P1M'),
            'Quarterly' => new DateInterval('P3M'),
            'Yearly' => new DateInterval('P1Y'),
            default => null,
        };

        if ($interval === null) {
            return null;
        }

        $date->add($interval);
        return $date->format('Y-m-d');
    } catch (Exception $e) {
        error_log('Error calculating next date: ' . $e->getMessage());
        return null;
    }
}

/**
 * Process all active recurring expenses and generate new expense entries
 *
 * @param PDO $pdo Database connection
 * @return array Result array with 'success' and 'generated_count' keys
 */
function processRecurringExpenses(PDO $pdo): array
{
    $today = date('Y-m-d');
    $generatedCount = 0;
    $errors = [];

    try {
        // Fetch all active recurring expenses
        $stmt = $pdo->prepare(
            'SELECT id, user_id, title, amount, category_id, recurrence_type, 
                    last_generated_date, end_date, description
             FROM recurring_expenses
             WHERE is_active = 1 AND start_date <= :today AND (end_date IS NULL OR end_date >= :today)
             ORDER BY id'
        );
        $stmt->execute(['today' => $today]);
        $recurringExpenses = $stmt->fetchAll();

        // Process each recurring expense
        foreach ($recurringExpenses as $recurring) {
            $nextDate = calculateNextDate($recurring['last_generated_date'], $recurring['recurrence_type']);

            if ($nextDate === null) {
                $errors[] = "Failed to calculate next date for recurring expense {$recurring['id']}";
                continue;
            }

            // Check if we should generate this expense
            if ($nextDate > $today) {
                continue;
            }

            // Check if it exceeds end date
            if ($recurring['end_date'] !== null && $nextDate > $recurring['end_date']) {
                continue;
            }

            // Generate the expense
            try {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO expenses (user_id, title, amount, category_id, expense_date, description)
                     VALUES (:user_id, :title, :amount, :category_id, :expense_date, :description)'
                );
                $insertStmt->execute([
                    'user_id' => $recurring['user_id'],
                    'title' => $recurring['title'],
                    'amount' => $recurring['amount'],
                    'category_id' => $recurring['category_id'],
                    'expense_date' => $nextDate,
                    'description' => $recurring['description'] ? '[Recurring] ' . $recurring['description'] : '[Recurring Expense]',
                ]);

                // Update last_generated_date
                $updateStmt = $pdo->prepare(
                    'UPDATE recurring_expenses SET last_generated_date = :next_date WHERE id = :recurring_id'
                );
                $updateStmt->execute([
                    'next_date' => $nextDate,
                    'recurring_id' => $recurring['id'],
                ]);

                $generatedCount++;
            } catch (PDOException $e) {
                $errors[] = "Failed to generate expense for recurring {$recurring['id']}: " . $e->getMessage();
                error_log("Failed to generate expense: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'generated_count' => $generatedCount,
            'errors' => $errors,
        ];
    } catch (Exception $e) {
        error_log('Error in processRecurringExpenses: ' . $e->getMessage());
        return [
            'success' => false,
            'generated_count' => 0,
            'errors' => [$e->getMessage()],
        ];
    }
}

/**
 * Toggle the active status of a recurring expense
 *
 * @param PDO $pdo Database connection
 * @param int $recurringId The recurring expense ID
 * @param int $userId The user ID (for authorization)
 * @return bool True if successful, false otherwise
 */
function toggleRecurringExpenseStatus(PDO $pdo, int $recurringId, int $userId): bool
{
    try {
        $stmt = $pdo->prepare(
            'UPDATE recurring_expenses SET is_active = NOT is_active 
             WHERE id = :recurring_id AND user_id = :user_id'
        );
        return $stmt->execute([
            'recurring_id' => $recurringId,
            'user_id' => $userId,
        ]);
    } catch (PDOException $e) {
        error_log('Failed to toggle recurring expense status: ' . $e->getMessage());
        return false;
    }
}
