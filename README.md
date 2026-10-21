# Smart Hostel Complaint Management System (v1.0)

**Software Requirements Specification (SRS) Implementation**  
**Software Engineering Laboratory Project**  
**Student Developer:** Yana Sareen  
**Registration Number:** 2427030570  
**Course:** Software Engineering Laboratory  

---

## 🌟 Executive Overview & "Smart" Innovations

The **Smart Hostel Complaint Management System** is a modern, responsive web application designed according to IEEE SRS standards. Unlike traditional hostel management applications that serve merely as static logbooks, this system introduces **six smart proactive features**:

1. ⚡ **Smart Duplicate Complaint Identification (FR-18)**  
   - Prevents multiple students from reporting the same broken corridor router or leaking plumbing line.
   - When a student selects a room/location and category, the system checks open tickets in real-time and alerts the student, automatically grouping duplicate tickets for efficient technician dispatch.

2. ⏰ **Automated SLA Escalation Matrix (FR-19, TBD-01)**  
   - High-priority electrical or safety hazards escalate to the Chief Warden and Administrator if unresolved within 24 hours.
   - Escalated tickets visually pulse with urgent alerts in the warden command center.

3. ⭐ **Transparent Resolution & 5-Star Feedback Loop (FR-20, BR-07)**  
   - Staff cannot arbitrarily close issues without student verification. Students submit a 1 to 5 star rating and comment upon resolution, driving staff accountability and satisfaction metrics.

4. 🧾 **Instant Digital Fee Receipt Generation & Print Layout (FR-21 – FR-24, BR-06)**  
   - Accountants record payments (Bank Transfer, UPI, Cheque, Cash) and instantly generate an official university receipt with unique serial numbers, watermark, and print styling.

5. 🔔 **Interactive In-App Notification Center (FR-17, TBD-02)**  
   - Unread notification badges and live updates inform students and staff of status changes, room approvals, and overdue tickets.

6. 📊 **Real-time Analytics & Activity Reports (FR-26 – FR-28)**  
   - Visual KPI metric cards, category breakdown progress charts, occupancy visualizer, and customizable printable administrative reports.

---

## 👥 4 Distinct User Roles & SRS Compliance Matrix

| Requirement | Description | Target Role | Implementation File | Status |
|---|---|---|---|---|
| **FR-01** | Student self-registration with personal & academic details | Student | [`register.php`](register.php) | ✅ Complete |
| **FR-02** | Secure credential authentication (`password_hash`) | All Users | [`login.php`](login.php) | ✅ Complete |
| **FR-03** | Role-based permission routing and access control | All Users | [`config.php`](config.php) | ✅ Complete |
| **FR-04** | Form validation & clear error message rejection | All Users | [`register.php`](register.php), [`login.php`](login.php) | ✅ Complete |
| **FR-05** | Student accommodation application submission | Student | [`student_dashboard.php`](student_dashboard.php) | ✅ Complete |
| **FR-06** | Warden reviews accommodation applications (Approve / Reject) | Warden | [`warden_dashboard.php`](warden_dashboard.php) | ✅ Complete |
| **FR-07** | Warden allocates available rooms to approved students | Warden | [`warden_dashboard.php`](warden_dashboard.php) | ✅ Complete |
| **FR-08** | Room inventory, bed capacity, and maintenance control | Warden / Admin | [`admin_dashboard.php`](admin_dashboard.php) | ✅ Complete |
| **FR-09** | Record student campus arrival check-in and departure check-out | Warden | [`warden_dashboard.php`](warden_dashboard.php) | ✅ Complete |
| **FR-10** | Student submits complaint with category & description | Student | [`student_dashboard.php`](student_dashboard.php) | ✅ Complete |
| **FR-11** | Categories: Electricity, Plumbing, Wi-Fi, Cleaning, Furniture, Other | Student | [`student_dashboard.php`](student_dashboard.php) | ✅ Complete |
| **FR-12** | Unique ticket ID generation (`CMP-YYYY-XXXX`) and initial 'Pending' status | Student | [`student_dashboard.php`](student_dashboard.php) | ✅ Complete |
| **FR-13** | Supported complaint priorities: High, Medium, Low | Student | [`student_dashboard.php`](student_dashboard.php) | ✅ Complete |
| **FR-14** | Students view status and history of their own complaints | Student | [`student_dashboard.php`](student_dashboard.php) | ✅ Complete |
| **FR-15** | Staff updates complaint status: Pending → In Progress → Resolved | Warden | [`warden_dashboard.php`](warden_dashboard.php) | ✅ Complete |
| **FR-16** | System retains historical complaint records for auditing | System | [`db.php`](db.php), [`schema.sql`](schema.sql) | ✅ Complete |
| **FR-17** | Real-time in-app notification alerts for status updates | All Users | [`includes/navbar.php`](includes/navbar.php) | ✅ Complete |
| **FR-18** | Duplicate complaint identification engine | Student / Warden | [`assets/js/app.js`](assets/js/app.js), [`api.php`](api.php) | ✅ Complete |
| **FR-19** | Automated SLA complaint escalation matrix (24h/48h/72h) | Warden / Admin | [`db.php`](db.php), [`warden_dashboard.php`](warden_dashboard.php) | ✅ Complete |
| **FR-20** | Post-resolution 1-to-5 star student rating & feedback loop | Student | [`student_dashboard.php`](student_dashboard.php) | ✅ Complete |
| **FR-21** | Maintenance of hostel fee structures & payment history | Accountant | [`accountant_dashboard.php`](accountant_dashboard.php) | ✅ Complete |
| **FR-22** | Authorized recording and verification of student fee payments | Accountant | [`accountant_dashboard.php`](accountant_dashboard.php) | ✅ Complete |
| **FR-23** | Official printable receipt generation with unique receipt numbers | Accountant | [`receipt_print.php`](receipt_print.php) | ✅ Complete |
| **FR-24** | Students inspect fee status, history, and printable receipts | Student | [`student_dashboard.php`](student_dashboard.php) | ✅ Complete |
| **FR-25** | Administrator manages student records, profiles, and statuses | Admin | [`admin_dashboard.php`](admin_dashboard.php) | ✅ Complete |
| **FR-26** | Generate date-filtered reports on complaints and hostel activity | Admin | [`admin_dashboard.php`](admin_dashboard.php) | ✅ Complete |
| **FR-27** | Visual analytics dashboard (totals, pending, resolved, categories) | Admin | [`admin_dashboard.php`](admin_dashboard.php) | ✅ Complete |
| **FR-28** | Reports include room occupancy and fee collection audits | Admin | [`admin_dashboard.php`](admin_dashboard.php) | ✅ Complete |

---

## 🔑 Pre-Configured Evaluation Accounts

For laboratory evaluation, the application includes a **top evaluation bar** with 1-click role switching, as well as credentials:

| Role | Email Address | Password | Persona & Key Actions |
|---|---|---|---|
| **Student** | `student@hostel.edu` | `student123` | **Yana Sareen (Reg: 2427030570)** — File complaints, view duplicate alerts, submit 5-star ratings, download receipts |
| **Hostel Warden** | `warden@hostel.edu` | `warden123` | **Dr. R. K. Sharma** — Approve room requests, allocate rooms, check-in residents, dispatch repairs, monitor escalations |
| **Accountant** | `accountant@hostel.edu` | `accountant123` | **Mr. Alok Verma** — Record fee payments, generate formal receipts with unique numbers |
| **Administrator** | `admin@hostel.edu` | `admin123` | **System Administrator** — Central analytics KPIs, category distribution charts, student management, print reports |

---

## 🚀 How to Run the Application

The application is completely self-contained with a universal PDO database layer. It supports running via **XAMPP** or directly via **PHP's built-in web server**.

### Option A: Running via PHP CLI (Zero-Configuration, Recommended for Quick Testing)

1. Open Terminal in this folder:
   ```bash
   cd "/Users/yanasareen/Desktop/se hostel management "
   ```
2. Start the PHP server:
   ```bash
   php -S 127.0.0.1:8000
   ```
3. Open your browser and navigate to:
   [http://127.0.0.1:8000](http://127.0.0.1:8000)

*Note: If MySQL is not started, the application automatically uses an embedded SQLite database (`smart_hostel.sqlite`) pre-seeded with all demo accounts and complaints.*

### Option B: Running via XAMPP (Apache + MySQL)

1. A symlink has already been configured to your XAMPP web directory:
   - macOS Symlink: `/Applications/XAMPP/xamppfiles/htdocs/hostel` -> `/Users/yanasareen/Desktop/se hostel management `
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Access the web portal directly at:
   [http://localhost/hostel](http://localhost/hostel)
   *(Or via PHP CLI at [http://127.0.0.1:8000](http://127.0.0.1:8000))*

---

## 🛠️ Multi-Page System Architecture & Dedicated Endpoints

Every navbar link and action reloads into a dedicated, focused page with its own URL, state management, and visual analytics:

```
se hostel management/
├── config.php                      # Core settings, constants, CSRF, flash helpers, role access
├── db.php                          # Universal PDO layer (XAMPP MySQL Socket + TCP + SQLite fallback)
├── schema.sql                      # Complete MySQL schema dump with relational tables
├── setup.php                       # Interactive 1-click database installer & reset tool
├── api.php                         # Backend AJAX endpoint (duplicate detection, mark read, demo switch)
├── index.php                       # Public portal homepage & room showcase
├── login.php                       # Secure authentication (password_verify) & quick-fill buttons
├── register.php                    # Student self-registration form (FR-01)
├── logout.php                      # Session termination
│
├── [STUDENT PORTAL]
│   ├── student_dashboard.php       # Dedicated Dashboard with Chart.js Complaints Bar Graph & Category Doughnut
│   ├── apply_room.php              # Dedicated Room Application form & submission tracker (FR-05)
│   ├── available_rooms.php         # Dedicated Available Rooms explorer with filter & occupancy (FR-08)
│   ├── my_complaints.php           # Dedicated Complaint Submission (FR-10), Real-time Duplicate Alert (FR-18), & 5-Star Feedback (FR-20)
│   └── my_fees.php                 # Dedicated Fees ledger & receipt launcher (FR-24)
│
├── [WARDEN PORTAL]
│   ├── warden_dashboard.php        # Dedicated Overview with Chart.js Bar Graphs, SLA Escalations (FR-19), & KPIs
│   ├── warden_applications.php     # Dedicated Room Applications Review Hub (Approve / Reject) (FR-06)
│   ├── warden_allocation.php       # Dedicated Room Allocation Hub with capacity check (FR-07, BR-02)
│   ├── warden_checkin.php          # Dedicated Check-in / Check-out Register (FR-09)
│   └── warden_complaints.php       # Dedicated Complaint Dispatch Hub with SLA tracker & status updates (FR-15)
│
├── [ACCOUNTANT PORTAL]
│   ├── accountant_dashboard.php    # Dedicated Financial Analytics with Chart.js Payment Mode Bar Graph & Category breakdown
│   ├── accountant_record_payment.php # Dedicated Fee Entry form (FR-22)
│   └── accountant_receipts.php     # Dedicated Fee Receipts ledger & search (FR-23)
│
├── [ADMINISTRATOR PORTAL]
│   ├── admin_dashboard.php         # Dedicated Central Command with Chart.js Complaints Analytics (FR-27)
│   ├── admin_students.php          # Dedicated Student Directory & Record Manager (FR-25)
│   ├── admin_rooms.php             # Dedicated Room Inventory Manager (FR-08)
│   └── admin_reports.php           # Dedicated Date-Filtered Administrative Reports Generator (FR-26, FR-28)
│
├── receipt_print.php               # Formal printable receipt layout with university letterhead (FR-23)
├── includes/
│   ├── header.php                  # HTML head, Tailwind CSS, Chart.js CDN, Lucide Icons, sticky evaluation bar
│   ├── navbar.php                  # Dedicated multi-page navigation links with active tab indicator & notifications
│   └── footer.php                  # Laboratory project metadata and script loaders
└── assets/
    ├── css/
    │   └── styles.css              # Custom animations, star rating styles, @media print
    └── js/
        └── app.js                  # Real-time duplicate checker, star rating interaction, modal controller
```

---

## 📝 Academic Compliance
This project satisfies all conditions outlined in the IEEE Software Requirements Specification (SRS) for the Software Engineering Laboratory Project by Yana Sareen (Reg: 2427030570).
