# 🎮 SYS_Playstation Cafe Management (ERP)

A comprehensive, multi-branch ERP system built with **Laravel** to manage Playstation cafes, inventory, and financial reporting.

##  Key Technical Highlights
- **Automated Billing Engine:** Custom logic to calculate session costs dynamically using time-difference equations (`start_at` / `end_at`).
- **Multi-tenant Inventory:** Advanced filtering system to isolate inventory and sales data per branch, ensuring data integrity.
- **Integrated POS:** A seamless checkout flow for cafe items (Menu/Takeaway) with automated real-time inventory deduction.
- **Reporting System:** Robust backend APIs providing daily and monthly financial summaries.
- **Security:** Custom Middleware-based authorization to protect branch-specific resources.

##  Tech Stack
- **Backend:** Laravel (PHP)
- **Database:** MySQL (Relational Schema for Branches, Inventory, and Sessions)
- **API:** RESTful endpoints for React frontend integration.
- **Auth:** Custom Middleware for Role/Branch-based access control.

##  Core Modules
1. **Session Management:** Real-time tracking of gaming sessions.
2. **Inventory Control:** Branch-specific stock management and tracking.
3. **HR & Payroll:** Attendance tracking and salary management for staff.
4. **Invoicing:** Automated generation of detailed invoices including session and menu costs.

##  Installation
1. Clone the repo: `git clone https://github.com/EslamMeky/SYS-Playstation-Cafe.git`
2. Install dependencies: `composer install`
3. Configure your `.env` for Database settings.
4. Run migrations: `php artisan migrate --seed`

---
Developed with focus on Performance & Scalability by [Eslam Mekky](https://github.com/EslamMeky)
