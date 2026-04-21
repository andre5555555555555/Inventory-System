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
        document.querySelector(".nav-links")?.classList.toggle("show");
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
                cleanMessage = data.message; // fallback (safe message)
            }

            showToast(cleanMessage, "error");
            return;
        }

            showToast("Deleted successfully", "success");
            window.location.reload();
        }

        // ── Activate / Deactivate users ──

        async function activateUser(id) {
            if (!confirm("Activate this user?")) return;

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
        }

        async function deactivateUser(id) {
            if (!confirm("Deactivate this user?")) return;

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
        };

        window.rejectStockoutItem = async function(itemId) {
            if (!confirm("Reject this item?")) return;

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
        };

        window.approveAllRequest = async function(requestId) {
            if (!confirm("Approve ALL items in this request? Stock will be deducted for each.")) return;

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
        };
    }

    // =========================
    // INIT
    // =========================

    document.addEventListener("DOMContentLoaded", () => {
        document.querySelector("[data-menu-toggle]")?.addEventListener("click", toggleMenu);
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