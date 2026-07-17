(() => {

    // =========================
    // HELPERS
    // =========================

    function readJsonAttribute(element, attributeName = "data-page") {
        if (!element) return {};

        const payload = element.getAttribute(attributeName);
        if (!payload) return {};

        try {
            return JSON.parse(payload);
        } catch (error) {
            console.error(`Unable to parse ${attributeName}`, error);
            return {};
        }
    }

    function readJsonScript(id) {
        const element = document.getElementById(id);
        if (!element) return {};

        try {
            return JSON.parse(element.textContent ?? "{}");
        } catch (error) {
            console.error(`Unable to parse JSON script #${id}`, error);
            return {};
        }
    }

    function csrfHeaders() {
        const meta = document.querySelector(`meta[name="${window.appConfig?.csrfHeader ?? ""}"]`);
        const headers = {
            "X-Requested-With": "XMLHttpRequest",
        };

        if (meta && window.appConfig?.csrfHeader) {
            headers[window.appConfig.csrfHeader] = meta.content;
        }

        return headers;
    }

    const busyActions = new Set();

    function setBusyState(element, isBusy) {
        if (!element) return;

        element.classList.toggle("is-busy", isBusy);
        element.dataset.busy = isBusy ? "true" : "false";
        element.setAttribute("aria-busy", isBusy ? "true" : "false");

        if (element.matches("button, input[type='submit'], input[type='button']")) {
            element.disabled = isBusy;
        } else if (element.matches("a")) {
            if (isBusy) {
                element.setAttribute("aria-disabled", "true");
            } else {
                element.removeAttribute("aria-disabled");
            }
        }
    }

    async function runOnce(actionKey, trigger, task) {
        if (busyActions.has(actionKey)) return null;

        busyActions.add(actionKey);
        setBusyState(trigger, true);

        try {
            return await task();
        } finally {
            busyActions.delete(actionKey);
            setBusyState(trigger, false);
        }
    }

    function initThemeToggle() {
        const root = document.documentElement;
        const toggle = document.querySelector("[data-theme-toggle]");
        if (!toggle) return;
        const label = toggle.querySelector("[data-theme-label]");
        const themes = ["light", "dark", "bsu"];

        function applyTheme(theme) {
            const currentTheme = themes.includes(theme) ? theme : "light";
            const nextTheme = themes[(themes.indexOf(currentTheme) + 1) % themes.length];
            const themeNames = {
                light: "Light",
                dark: "Dark",
                bsu: "BSU",
            };
            const labelText = `${themeNames[currentTheme]} mode. Click for ${themeNames[nextTheme]} mode.`;

            root.dataset.theme = currentTheme;
            toggle.dataset.themeState = currentTheme;
            localStorage.setItem("inventoryTheme", currentTheme);
            toggle.setAttribute("aria-label", labelText);
            toggle.setAttribute("title", labelText);

            if (label) {
                label.textContent = labelText;
            }
        }

        if (root.dataset.theme === "rpg" || root.dataset.theme === "BSU") {
            root.dataset.theme = "bsu";
        }

        applyTheme(themes.includes(root.dataset.theme) ? root.dataset.theme : "light");

        toggle.addEventListener("click", () => {
            const currentIndex = themes.indexOf(root.dataset.theme);
            applyTheme(themes[(currentIndex + 1) % themes.length]);
        });
    }

    function bindSubmitGuards() {
        document.addEventListener("submit", (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
            if (form.dataset.noSubmitGuard === "true") return;

            if (form.dataset.submitting === "true") {
                event.preventDefault();
                return;
            }

            if (typeof form.checkValidity === "function" && !form.checkValidity()) return;

            form.dataset.submitting = "true";
            form.querySelectorAll("button[type='submit'], input[type='submit']").forEach((button) => {
                setBusyState(button, true);
            });
        });

        document.addEventListener("click", (event) => {
            const trigger = event.target.closest("button, a.action-btn, a.btn-add, a.btn-primary, a.btn-secondary");
            if (!trigger) return;

            if (trigger.matches("[data-theme-toggle], [data-menu-toggle], [data-stockcard-toggle], [data-filter-open], [data-filter-close], .section-header, .close, .close-btn")) {
                return;
            }

            if (trigger.dataset.busy === "true" || trigger.getAttribute("aria-disabled") === "true") {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, true);
    }

    // =========================
    // TOAST SYSTEM
    // =========================
    function showToast(message, type = "success", duration = 3000) {
    const toast = document.createElement("div");
    toast.className = `flash-message flash-${type}`;

    // message text
    const text = document.createElement("span");
    text.textContent = message;

    // progress bar (timer visual)
    const progress = document.createElement("div");
    progress.className = "flash-progress";

    toast.appendChild(text);
    toast.appendChild(progress);

    document.body.appendChild(toast);

    // force reflow for animation
    setTimeout(() => {
        progress.style.width = "0%";
    }, 50);

    // auto remove
    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "scale(0.95)";

        setTimeout(() => {
            toast.remove();
        }, 200);
    }, duration);
}
    function toggleMenu() {
        const navLinks = document.querySelector(".nav-links");
        if (!navLinks) return;

        const isOpen = navLinks.classList.toggle("show");
        navLinks.classList.toggle("active", isOpen);

        if (!isOpen) {
            document.querySelectorAll(".nav-links .has-submenu.active").forEach((item) => {
                item.classList.remove("active");
            });
        }
    }

    function toggleMobileSubmenu(trigger) {
        const parent = trigger?.closest(".has-submenu");
        if (!parent) return;

        const isOpen = parent.classList.toggle("active");
        document.querySelectorAll(".nav-links .has-submenu").forEach((item) => {
            if (item !== parent) item.classList.remove("active");
        });

        const navLinks = document.querySelector(".nav-links");
        navLinks?.classList.add("show");
        navLinks?.classList.add("active");

        return isOpen;
    }

    // =========================
    // STOCK / GENERAL MODULES
    // =========================

    function bindItemRedirects() {
        document.querySelectorAll("[data-stock-item-select], [data-adjust-select]").forEach((select) => {
            select.addEventListener("change", () => {
                const itemId = select.value;
                const targetUrl =
                    select.getAttribute("data-target-url") ||
                    select.closest("[data-adjust-form]")?.getAttribute("data-target-url");

                if (!itemId || !targetUrl) return;

                window.location.href = `${targetUrl}?item_id=${encodeURIComponent(itemId)}`;
            });
        });
    }

    function bindAdjustSearch() {
        const searchInput = document.querySelector("[data-adjust-search]");
        const select = document.querySelector("[data-adjust-select]");
        if (!searchInput || !select) return;

        searchInput.addEventListener("input", () => {
            const query = searchInput.value.trim().toLowerCase();

            Array.from(select.options).forEach((option, index) => {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                const label =
                    (option.getAttribute("data-label") ?? option.textContent).toLowerCase();

                option.hidden = query !== "" && !label.includes(query);
            });
        });
    }

    function bindStockcardTools() {
        const layout = document.querySelector("[data-stockcard-layout]");
        if (!layout) return;

        const sidebar = document.getElementById("itemSidebar");
        const backdrop = document.querySelector("[data-stockcard-backdrop]");
        const searchInput = document.querySelector("[data-stockcard-search]");
        const items = Array.from(document.querySelectorAll("[data-stockcard-item]"));

        function setSidebarState(isOpen) {
            sidebar?.classList.toggle("is-open", isOpen);
            backdrop?.classList.toggle("is-visible", isOpen);
            document.body.classList.toggle("stockcard-sidebar-open", isOpen);
        }

        document.querySelectorAll("[data-stockcard-toggle]").forEach((button) => {
            button.addEventListener("click", () => {
                setSidebarState(!sidebar?.classList.contains("is-open"));
            });
        });

        backdrop?.addEventListener("click", () => setSidebarState(false));

        searchInput?.addEventListener("input", () => {
            const query = searchInput.value.trim().toLowerCase();

            items.forEach((item) => {
                const label = item.getAttribute("data-label") ?? "";
                item.style.display = query === "" || label.includes(query) ? "" : "none";
            });
        });
    }

    // =========================
    // SETTINGS MODULE
    // =========================

    function initSettingsPage() {
        const page = document.getElementById("settingsPage");
        if (!page) return;

        const config = readJsonScript("settingsConfigJson");

        const titles = {
            users: "User",
            entity: "Entity",
            unit: "Unit",
            roles: "Role",
            reference: "Reference",
            item_type: "Item Type",
            item_category: "Category",
            office: "Office",
            user_office: "User Office",
        };

        const modal = document.getElementById("modal");
        const fields = document.getElementById("fields");
        const modalTitle = document.getElementById("modalTitle");
        const modalForm = document.getElementById("modalForm");
        const recordTypeInput = document.getElementById("recordType");
        const recordIdInput = document.getElementById("recordId");

        const savedSection = localStorage.getItem("activeSettingsSection");

        if (savedSection) {
            document.querySelectorAll(".settings-section-body").forEach(el => {
                el.classList.remove("is-open");
            });

            document.querySelectorAll(".settings-section-header").forEach(el => {
                el.classList.remove("active");
            });

            const section = document.getElementById(savedSection);
            const button = document.querySelector(`[onclick*="${savedSection}"]`);

            if (section) {
                section.classList.add("is-open");
                button?.classList.add("active");
            }
        }

        function createField(type, field, label, value = "") {
            const wrapper = document.createElement("div");
            wrapper.className = "settings-field";

            const fieldLabel = document.createElement("label");
            fieldLabel.setAttribute("for", `field-${field}`);
            fieldLabel.textContent = label;

            let input;

            if (type === "users" && field === "role") {
                input = document.createElement("select");

                (config.roles ?? []).forEach((role) => {
                    const option = document.createElement("option");
                    option.value = role.role_name;
                    option.textContent = role.role_name;
                    option.selected = role.role_name === String(value ?? "");
                    input.appendChild(option);
                });

            } else if (type === "users" && field === "user_office_id") {
                input = document.createElement("select");

                const placeholder = document.createElement("option");
                placeholder.value = "";
                placeholder.textContent = "Select user office";
                input.appendChild(placeholder);

                (config.userOffices ?? []).forEach((uo) => {
                    const option = document.createElement("option");
                    option.value = uo.user_office_id;
                    option.textContent = uo.user_office;
                    option.selected = String(uo.user_office_id) === String(value ?? "");
                    input.appendChild(option);
                });

            } else if (type === "roles" && field === "level_id") {
                input = document.createElement("select");

                const lvlPlaceholder = document.createElement("option");
                lvlPlaceholder.value = "";
                lvlPlaceholder.textContent = "Select Level of Access";
                input.appendChild(lvlPlaceholder);

                (config.levels ?? []).forEach((level) => {
                    const option = document.createElement("option");
                    option.value = level.level_id;
                    option.textContent = `Level ${level.level_id}`;
                    option.selected = String(level.level_id) === String(value ?? "");
                    input.appendChild(option);
                });

            } else {
                input = document.createElement("input");
                input.type = field === "password" ? "password" : (field === "email" ? "email" : "text");
                input.value = value ?? "";

                if (field === "password") {
                    input.placeholder = "Leave blank to keep current password";
                }
                if (field === "email") {
                    input.placeholder = "Enter email address";
                }
            }

            input.name = field;
            input.id = `field-${field}`;
            if (field !== "password") input.required = true;

            wrapper.append(fieldLabel, input);
            return wrapper;
        }

        function fillForm(type, row = {}) {
            const definition = config.definitions?.[type];
            if (!definition) return;

            fields.innerHTML = "";
            recordTypeInput.value = type;
            recordIdInput.value = row[definition.pk] ?? "";

            definition.fields.forEach((field) => {
                const label = definition.labels?.[field] ?? field;
                fields.appendChild(createField(type, field, label, row[field] ?? ""));
            });
        }

        async function openModal(type, id = null) {
            modalTitle.textContent = `${id ? "Edit" : "Add"} ${titles[type] ?? type}`;

            if (!id) {
                fillForm(type);
                modal.classList.add("is-open");
                return;
            }

            const response = await fetch(`${config.fetchBase}/${type}/${id}`, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });

            const row = await response.json();
            fillForm(type, row);
            modal.classList.add("is-open");
        }

        function closeModal() {
            modal.classList.remove("is-open");
            modalForm.reset();
            fields.innerHTML = "";
        }

        async function deleteRecord(type, id) {
            if (!confirm("Delete this record?")) return;

            const trigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;

            await runOnce(`settings-delete-${type}-${id}`, trigger, async () => {
                localStorage.setItem("activeSettingsSection", `section-${type}`);

                const response = await fetch(`${config.deleteBase}/${type}/${id}`, {
                    method: "POST",
                    headers: csrfHeaders(),
                });

                const data = await response.json();

                if (!response.ok) {
                    const msg = (data.message || "").toLowerCase();
                    let cleanMessage = "Delete failed";

                    if (msg.includes("foreign key") || msg.includes("constraint")) {
                        cleanMessage = "Delete failed: This item is currently in use and cannot be removed.";
                    } else if (msg.includes("not found")) {
                        cleanMessage = "Delete failed: Record not found.";
                    } else if (msg.includes("permission")) {
                        cleanMessage = "Delete failed: You do not have permission.";
                    } else if (msg) {
                        cleanMessage = data.message;
                    }

                    showToast(cleanMessage, "error");
                    return;
                }

                showToast("Deleted successfully", "success");
                window.location.reload();
            });
        }

        // â”€â”€ Activate / Deactivate users â”€â”€

        async function activateUser(id) {
            if (!confirm("Activate this user?")) return;

            const trigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;

            await runOnce(`settings-activate-${id}`, trigger, async () => {
                const response = await fetch(`${config.activateBase}/${id}`, {
                    method: "POST",
                    headers: csrfHeaders(),
                });

                const data = await response.json();

                if (!response.ok) {
                    showToast(data.message ?? "Activation failed", "error");
                    return;
                }

                showToast("User activated successfully", "success");
                window.location.reload();
            });
        }

        async function deactivateUser(id) {
            if (!confirm("Deactivate this user?")) return;

            const trigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;

            await runOnce(`settings-deactivate-${id}`, trigger, async () => {
                const response = await fetch(`${config.deactivateBase}/${id}`, {
                    method: "POST",
                    headers: csrfHeaders(),
                });

                const data = await response.json();

                if (!response.ok) {
                    showToast(data.message ?? "Deactivation failed", "error");
                    return;
                }

                showToast("User deactivated successfully", "success");
                window.location.reload();
            });
        }

        function filterTable(input, tableId) {
            const table = document.getElementById(tableId);
            if (!table) return;

            const query = input.value.toLowerCase();

            Array.from(table.querySelectorAll("tr")).slice(1).forEach((row) => {
                row.style.display =
                    row.textContent.toLowerCase().includes(query) ? "" : "none";
            });
        }

        function toggleSection(sectionId, button) {
            const section = document.getElementById(sectionId);
            if (!section) return;

            const isOpen = section.classList.toggle("is-open");
            button.classList.toggle("active", isOpen);

            if (isOpen) {
                localStorage.setItem("activeSettingsSection", sectionId);
            }
        }

        modalForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            const type = recordTypeInput.value;
            const submitter = event.submitter ?? modalForm.querySelector("button[type='submit']");

            await runOnce(`settings-save-${type}`, submitter, async () => {
                localStorage.setItem("activeSettingsSection", `section-${type}`);

                const formData = new FormData(modalForm);

                const response = await fetch(`${config.saveBase}/${type}`, {
                    method: "POST",
                    headers: csrfHeaders(),
                    body: new URLSearchParams(formData),
                });

                const data = await response.json();

                if (!response.ok) {
                    showToast(data.message ?? "Save failed", "error");
                    return;
                }

                showToast("Saved successfully", "success");
                window.location.reload();
            });
        });

        window.toggleSection = toggleSection;
        window.openModal = openModal;
        window.closeModal = closeModal;
        window.deleteRecord = deleteRecord;
        window.searchTable = filterTable;
        window.activateUser = activateUser;
        window.deactivateUser = deactivateUser;

        modal.addEventListener("click", (event) => {
            if (event.target === modal) closeModal();
        });
    }

    // =========================
    // STOCKOUT APPROVAL MODULE
    // =========================

    function initStockoutApproval() {
        // Only init if on the pending page
        if (!document.querySelector('.pending-request-card')) return;

        window.approveStockoutItem = async function(itemId) {
            if (!confirm("Approve this item? Stock will be deducted.")) return;

            const trigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;

            await runOnce(`stockout-approve-${itemId}`, trigger, async () => {
                const response = await fetch(`${window.appConfig.baseUrl}stockout/approve-item/${itemId}`, {
                    method: "POST",
                    headers: csrfHeaders(),
                });

                const data = await response.json();

                if (!response.ok) {
                    showToast(data.message ?? "Approval failed", "error");
                    return;
                }

                showToast("Item approved", "success");
                window.location.reload();
            });
        };

        window.rejectStockoutItem = async function(itemId) {
            if (!confirm("Reject this item?")) return;

            const trigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;

            await runOnce(`stockout-reject-${itemId}`, trigger, async () => {
                const response = await fetch(`${window.appConfig.baseUrl}stockout/reject-item/${itemId}`, {
                    method: "POST",
                    headers: csrfHeaders(),
                });

                const data = await response.json();

                if (!response.ok) {
                    showToast(data.message ?? "Rejection failed", "error");
                    return;
                }

                showToast("Item rejected", "success");
                window.location.reload();
            });
        };

        window.approveAllRequest = async function(requestId) {
            if (!confirm("Approve ALL items in this request? Stock will be deducted for each.")) return;

            const trigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;

            await runOnce(`stockout-approve-all-${requestId}`, trigger, async () => {
                const response = await fetch(`${window.appConfig.baseUrl}stockout/approve-all/${requestId}`, {
                    method: "POST",
                    headers: csrfHeaders(),
                });

                const data = await response.json();

                if (!response.ok) {
                    showToast(data.message ?? "Approval failed", "error");
                    return;
                }

                showToast("All items approved", "success");
                window.location.reload();
            });
        };
    }

    // =========================
    // HOVER DROPDOWN MODULE
    // =========================

    function initHoverDropdowns() {
        document.querySelectorAll('.hover-dropdown').forEach(container => {
            const input = container.querySelector('.hover-input');
            const options = container.querySelectorAll('.hover-option');
            const dropdownContent = container.querySelector('.hover-dropdown-content');

            if (!input || !dropdownContent) return;

            input.addEventListener('input', () => {
                const query = input.value.trim().toLowerCase();
                options.forEach(option => {
                    const text = (option.textContent || '').toLowerCase();
                    if (query === '' || text.includes(query)) {
                        option.classList.remove('hidden');
                    } else {
                        option.classList.add('hidden');
                    }
                });
            });

            input.addEventListener('focus', () => {
                dropdownContent.classList.add('force-show');
            });
            input.addEventListener('blur', () => {
                setTimeout(() => {
                    dropdownContent.classList.remove('force-show');
                }, 200);
            });

            options.forEach(option => {
                option.addEventListener('click', () => {
                    input.value = option.textContent.trim();
                    dropdownContent.classList.remove('force-show');
                });
            });
        });
    }

    // =========================
    // INIT
    // =========================

    document.addEventListener("DOMContentLoaded", () => {
        initThemeToggle();
        bindSubmitGuards();
        initHoverDropdowns();

        document.querySelector("[data-menu-toggle]")?.addEventListener("click", toggleMenu);

        document.querySelectorAll(".nav-links .has-submenu > a").forEach((link) => {
            link.addEventListener("click", (event) => {
                if (!window.matchMedia("(max-width: 768px)").matches) return;
                event.preventDefault();
                toggleMobileSubmenu(link);
            });
        });

        bindItemRedirects();
        bindAdjustSearch();
        bindStockcardTools();
        initSettingsPage();
        initStockoutApproval();
          const flash = document.querySelector(".flash-message");

        if (flash) {
            setTimeout(() => {
                flash.style.opacity = "0";
                flash.style.transform = "scale(0.95)";

                setTimeout(() => {
                    flash.remove();
                }, 200);
            }, 1500); // 1.5 seconds
        }
    });

})();


