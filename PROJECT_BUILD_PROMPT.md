# Master Prompt: Build This Property Management Portal

You are a senior PHP/MySQL full-stack developer and UI/UX engineer. Build a complete, working property-management web application for the Lucknow Development Authority (LDA) using PHP 8+, MySQL, Bootstrap 5.3, vanilla JavaScript, HTML5 and CSS3.

The application must run directly inside XAMPP at:

`http://localhost/property-management/`

Do not create a mockup or static prototype. Create a functional application with working PHP pages, MySQL queries, sessions, forms, validation, file uploads, role permissions, responsive UI and database schema.

## 1. Project Structure

Create this structure:

```text
property-management/
├── index.php
├── login.php
├── logout.php
├── contact.php
├── citizen-portal.php
├── citizen-logout.php
├── create_admin.php
├── database.sql
├── update_v2.sql
├── lda_portal_schema.sql
├── README.md
├── config/
│   └── db.php
├── includes/
│   └── auth.php
├── api/
│   ├── search-property.php
│   └── citizen-otp.php
├── citizen/
├── dashboard/
│   ├── admin.php
│   ├── officer.php
│   ├── lda.php
│   ├── udc.php
│   ├── so.php
│   ├── properties.php
│   ├── property_detail.php
│   ├── add_property.php
│   ├── edit_property.php
│   ├── delete_property.php
│   ├── import_properties.php
│   ├── add_allottee.php
│   ├── payments.php
│   ├── manage_users.php
│   ├── edit_permissions.php
│   ├── module_lottery.php
│   ├── module_auction.php
│   ├── module_fcfs.php
│   └── module_hrms.php
└── assets/
    ├── css/style.css
    ├── sample_property_template.csv
    └── uploads/
        ├── allottees/
        └── properties/
```

## 2. Database

Create a MySQL database named `property_management`.

Create these tables with proper primary keys, foreign keys, indexes and timestamps:

- `roles`
- `users`
- `login_logs`
- `properties`
- `allottees`
- `user_permissions`
- `citizens`
- `online_applications`
- `citizen_requests`
- `grievances`
- `property_payments`
- `system_settings`
- `public_announcements`
- `faqs`

### Roles

Insert these roles:

- Admin
- Property Officer
- LDA
- UDC
- SO

### Users

Fields must include:

- id
- full_name
- username
- email
- password
- role_id
- status
- created_at

Use `password_hash()` and `password_verify()`. Never store plain text passwords in the database.

### Properties

Fields must include:

- scheme_name
- property_no
- property_code, unique
- address
- area_size
- price
- category: Lottery, Auction, FCFS, Direct Allotment
- status: Available, Pending, Sold, Allotted
- description
- image
- added_by
- created_at

### Allottees

Link every allottee to a property using `property_id`. Include:

- allottee_name
- father_name
- mobile
- aadhar_no
- pan_no
- aadhar_photo
- pan_photo
- address
- allotment_date

The citizen property matching must use `allottees.mobile` and the verified citizen phone number.

### System Settings

Add settings for:

- portal_name
- portal_contact_email
- portal_contact_phone
- helpline_number
- otp_validity_minutes
- max_otp_attempts

Use these settings in the UI. If the phone value is empty or contains `X`, display the dummy number `180018005001`. Keep the email `support@lda-portal.gov.in` or the configured database email.

## 3. Public Homepage

Create a polished responsive homepage in `index.php` with:

- Top utility bar with helpline, email and language links
- Main navbar with LDA branding
- Services link
- Announcements link
- FAQ link
- Contact link
- Officer Login button
- Hero section with LDA Property Portal heading
- Search Property button
- Citizen Login button
- Quick access service cards:
  - Property Search
  - Mutation Application
  - NOC Request
  - Online Payment
  - File Grievance
  - Citizen Portal
- Latest Announcements section
- Important Notice panel
- FAQ accordion
- Proper footer with contact details

If the announcements or FAQ database tables contain no active records, show useful default fallback content instead of leaving the section blank.

The page must never show unreadable text. The top bar background and text must have strong contrast. Use modern but professional government-portal styling with blue, orange and light neutral colors.

## 4. Officer Login

Create `login.php` with:

- Username field
- Password field
- Validation messages
- Secure database lookup
- Password verification
- Active/inactive account handling
- Login activity logging that must not block a valid login if the optional log table is unavailable
- Role-based redirect
- Clearly visible `Back to Home` button
- Responsive professional login card

Default admin setup:

- Username: `admin`
- Password: `admin123`

Create `create_admin.php` to create this account using a bcrypt hash. Explain in README that it must be deleted after first use.

## 5. Authentication and Authorization

Create `includes/auth.php` with:

- `is_logged_in()`
- `require_login()`
- `require_role()`
- `dashboard_redirect_path()`
- Category access helper functions
- Payment access helper functions

Protect every dashboard page with session authentication and role checks.

Admin can access every category. Other users can only access categories assigned through `user_permissions`.

## 6. Dashboard

Create separate dashboards for:

- Admin
- Property Officer
- LDA
- UDC
- SO

Admin dashboard must show:

- Total properties
- Lottery count
- Auction count
- FCFS count
- Direct Allotment count
- Recent properties table
- User management links
- Permission management links

Each dashboard and module page must have a consistent top header with:

- Current user name
- Functional Back button
- Logout button

The Back button must call browser history back and fall back to the dashboard or homepage if there is no history.

## 7. Property Management

Implement:

- Property list with filters by category and status
- Search by scheme, property number, property code and address
- Property detail page
- Add property form
- Edit property form
- Delete property action with confirmation
- CSV import
- Property image upload
- Allottee add/edit form
- Allottee Aadhar and PAN image uploads
- Automatic property status change to `Allotted` when an allottee is saved
- Payment ledger linked to each property

Use prepared statements everywhere. Escape all displayed database values with `htmlspecialchars()`.

## 8. Citizen Login with Demo OTP

Create a citizen login modal on the homepage.

Flow:

1. Citizen enters mobile number.
2. Frontend calls `api/citizen-otp.php` with `action=request`.
3. API returns success and the fixed local demo OTP `111000`.
4. OTP field becomes visible.
5. Citizen enters `111000`.
6. Frontend calls the same endpoint with `action=verify`.
7. API validates the demo OTP.
8. API creates or updates the citizen record.
9. API creates these session values:
   - `citizen_id`
   - `citizen_phone`
   - `citizen_name`
10. Redirect to `citizen-portal.php`.

This is a local/demo OTP only. Clearly label it in the UI as demo mode. Do not pretend an SMS was actually sent.

The API must validate request method, input and phone number. Return JSON responses and appropriate HTTP status codes.

## 9. Citizen Portal

Create `citizen-portal.php`.

Only allow access when a citizen session exists. Otherwise redirect to `index.php`.

Show only properties whose allottee mobile matches the verified citizen phone number. Support both:

- 10-digit phone format
- `+91` plus 10-digit phone format

For every linked property show:

- Scheme name
- Property number
- Property code
- Address
- Status
- Allottee information summary
- KYC status
- Apply Service button

The Apply Service modal must allow:

- Mutation
- KYC Update

Include a details textarea and submit button.

On submission:

- Verify that the property really belongs to the logged-in citizen
- Generate a unique reference number
- Store the request in `citizen_requests`
- Show a success message with the reference number

Also show a `My Applications` table containing:

- Reference number
- Property code
- Service type
- Status
- Submission date

Create `citizen-logout.php` to clear citizen session values.

## 10. Contact Page

Create `contact.php` with:

- LDA heading
- Helpline `180018005001`
- Configured email
- Office hours
- Office address
- Contact/support information cards
- Query form
- Back to Home button

## 11. Styling

Create a complete shared stylesheet at `assets/css/style.css`.

Requirements:

- Responsive desktop/tablet/mobile design
- Strong contrast in the top utility bar
- Professional LDA blue and orange color system
- Styled login page
- Styled dashboard header/sidebar/cards/tables
- Styled citizen portal cards and application table
- Styled contact page
- Clear buttons and form focus states
- No blank-looking sections when database content is empty
- Consistent Back button styling
- Avoid broken Unicode characters; use UTF-8 and valid icons/text

## 12. Security and Quality

- Use PDO prepared statements.
- Use sessions securely.
- Escape all output.
- Validate uploads by extension and file size.
- Do not expose passwords or database credentials in UI.
- Do not trust hidden form fields without server-side verification.
- Verify citizen property ownership server-side before accepting Mutation or KYC requests.
- Keep demo OTP clearly marked as development-only.
- Add helpful setup instructions in README.

## 13. Final Verification

After implementation:

1. Run PHP lint on every PHP file.
2. Import `database.sql` and required schema updates.
3. Verify homepage returns HTTP 200.
4. Verify login page returns HTTP 200.
5. Verify contact page returns HTTP 200.
6. POST a citizen OTP request and confirm OTP `111000` is returned.
7. Verify OTP `111000` and confirm citizen session creation.
8. Confirm citizen portal opens.
9. Add a test property and allottee with a mobile number.
10. Confirm that the matching citizen sees the property.
11. Submit Mutation and KYC requests.
12. Confirm reference numbers and application status display.
13. Verify every dashboard/form page has a working Back option.

Return the complete project files, SQL schema, setup steps, default admin credentials and a short list of all implemented features.
