(() => {
    const basePath = (() => {
        const path = window.location.pathname;
        if (path.includes('/pages/')) {
            return path.split('/pages/')[0] || '';
        }
        if (path.includes('/backend/')) {
            return path.split('/backend/')[0] || '';
        }
        const segments = path.split('/').filter(Boolean);
        if (segments.length <= 1) {
            return '';
        }
        return '/' + segments[0];
    })();

    const chartDefaults = {
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
            legend: {
                labels: {
                    color: '#253238',
                    font: {
                        family: 'Poppins, sans-serif',
                    },
                },
            },
        },
        scales: {
            x: {
                ticks: {
                    color: '#253238',
                },
                grid: {
                    color: 'rgba(0,0,0,0.08)',
                },
            },
            y: {
                ticks: {
                    color: '#253238',
                },
                grid: {
                    color: 'rgba(0,0,0,0.08)',
                },
            },
        },
    };

    const formatCurrency = (value) => `Rs ${Number(value || 0).toFixed(2)}`;

    async function loadAnalytics() {
        const needsCharts =
            document.getElementById('monthlyTrendChart') ||
            document.getElementById('categoryDistributionChart') ||
            document.getElementById('monthComparisonChart');
        const needsSummary = document.getElementById('currentMonthTotal') || document.getElementById('topCategoryName');

        if (!needsCharts && !needsSummary) {
            return;
        }

        try {
            const response = await fetch(`${basePath}/backend/actions/analytics_data.php`, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to fetch analytics data');
            }

            const data = await response.json();
            if (document.getElementById('monthlyTrendChart')) {
                renderMonthlyTrendChart(data.monthlyTrend || []);
            }
            if (document.getElementById('categoryDistributionChart')) {
                renderCategoryDistributionChart(data.categoryDistribution || []);
            }
            if (document.getElementById('monthComparisonChart')) {
                renderMonthComparisonChart(data.comparison || []);
            }
            if (needsSummary) {
                renderTopSummary(data);
            }
        } catch (error) {
            console.error(error);
        }
    }

    function renderMonthlyTrendChart(rows) {
        const canvas = document.getElementById('monthlyTrendChart');
        if (!canvas || !window.Chart) {
            return;
        }

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: rows.map((row) => row.month_label),
                datasets: [
                    {
                        label: 'Monthly Spend',
                        data: rows.map((row) => Number(row.total)),
                        borderColor: '#0f8b8d',
                        backgroundColor: 'rgba(15, 139, 141, 0.2)',
                        borderWidth: 3,
                        tension: 0.35,
                        fill: true,
                    },
                ],
            },
            options: chartDefaults,
        });
    }

    function renderCategoryDistributionChart(rows) {
        const canvas = document.getElementById('categoryDistributionChart');
        if (!canvas || !window.Chart) {
            return;
        }

        const colors = ['#ef476f', '#ffd166', '#06d6a0', '#118ab2', '#073b4c', '#8f5cff', '#ff7f50'];

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: rows.map((row) => row.category_name),
                datasets: [
                    {
                        data: rows.map((row) => Number(row.total)),
                        backgroundColor: rows.map((_, index) => colors[index % colors.length]),
                        borderColor: '#ffffff',
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                ...chartDefaults,
                scales: undefined,
            },
        });
    }

    function renderMonthComparisonChart(rows) {
        const canvas = document.getElementById('monthComparisonChart');
        if (!canvas || !window.Chart) {
            return;
        }

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: rows.map((row) => row.month_name),
                datasets: [
                    {
                        label: 'Total Spend',
                        data: rows.map((row) => Number(row.total)),
                        backgroundColor: '#118ab2',
                        borderRadius: 6,
                    },
                ],
            },
            options: chartDefaults,
        });
    }

    function renderTopSummary(data) {
        const currentMonthEl = document.getElementById('currentMonthTotal');
        if (currentMonthEl) {
            currentMonthEl.textContent = formatCurrency(data.currentMonthTotal || 0);
        }

        const topCategoryEl = document.getElementById('topCategoryName');
        const topCategoryTotalEl = document.getElementById('topCategoryTotal');
        if (topCategoryEl && topCategoryTotalEl) {
            topCategoryEl.textContent = data.topCategory?.category_name || 'N/A';
            topCategoryTotalEl.textContent = formatCurrency(data.topCategory?.total || 0);
        }
    }

    function wireExpenseModal() {
        const addModal = document.getElementById('addExpenseModal');
        const openAddButton = document.getElementById('openAddExpenseModal');
        const closeAddButton = document.getElementById('closeAddModal');
        const modal = document.getElementById('editExpenseModal');
        const closeButton = document.getElementById('closeEditModal');
        const editButtons = document.querySelectorAll('.edit-expense-btn');
        if (!modal && !addModal) {
            return;
        }

        const fields = {
            id: document.getElementById('edit_expense_id'),
            title: document.getElementById('edit_title'),
            amount: document.getElementById('edit_amount'),
            categoryId: document.getElementById('edit_category_id'),
            expenseDate: document.getElementById('edit_expense_date'),
            description: document.getElementById('edit_description'),
        };

        const openModal = () => {
            if (!modal) {
                return;
            }

            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
        };

        const closeModal = () => {
            if (!modal) {
                return;
            }

            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        };

        const openAddModal = () => {
            if (!addModal) {
                return;
            }

            addModal.classList.add('show');
            addModal.setAttribute('aria-hidden', 'false');
        };

        const closeAddModal = () => {
            if (!addModal) {
                return;
            }

            addModal.classList.remove('show');
            addModal.setAttribute('aria-hidden', 'true');
        };

        if (openAddButton) {
            openAddButton.addEventListener('click', openAddModal);
        }

        if (closeAddButton) {
            closeAddButton.addEventListener('click', closeAddModal);
        }

        if (addModal) {
            addModal.addEventListener('click', (event) => {
                if (event.target === addModal) {
                    closeAddModal();
                }
            });
        }

        if (editButtons.length > 0 && modal) {
            editButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    fields.id.value = button.dataset.expenseId || '';
                    fields.title.value = button.dataset.title || '';
                    fields.amount.value = button.dataset.amount || '';
                    fields.categoryId.value = button.dataset.categoryId || '';
                    fields.expenseDate.value = button.dataset.expenseDate || '';
                    fields.description.value = button.dataset.description || '';
                    openModal();
                });
            });
        }

        if (closeButton && modal) {
            closeButton.addEventListener('click', closeModal);
        }

        if (modal) {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeModal();
                closeAddModal();
            }
        });
    }

    loadAnalytics();
    wireExpenseModal();
})();
