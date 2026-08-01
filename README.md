# Property Management System - Setup Guide

## Step 1: XAMPP me file rakhein
Is poore `property-management` folder ko copy karke `htdocs` folder me rakh dein:
- Windows: `C:\xampp\htdocs\property-management`
- Mac: `/Applications/XAMPP/htdocs/property-management`

## Step 2: Database create karein
1. XAMPP Control Panel se **Apache** aur **MySQL** start karein.
2. Browser me `http://localhost/phpmyadmin` kholein.
3. `Import` tab pe jaake `database.sql` file select karein aur Go dabayein.
   (Ye `property_management` database aur uski tables bana dega.)

## Step 3: Admin account banayein
Browser me yeh URL kholein:
```
http://localhost/property-management/create_admin.php
```
Isse ek default Admin account ban jayega:
- **Username:** admin
- **Password:** admin123

⚠️ Is step ke baad `create_admin.php` file ko **delete** kar dein (security ke liye).

## Step 4: Login karein
```
http://localhost/property-management/login.php
```
Admin login karke `dashboard/admin.php` pe redirect ho jayega.

## Folder Structure
```
property-management/
├── config/
│   └── db.php              -> database connection
├── includes/
│   └── auth.php             -> login check / role check functions
├── dashboard/
│   ├── admin.php
│   ├── officer.php
│   ├── lda.php
│   ├── udc.php
│   └── so.php
├── login.php
├── logout.php
├── create_admin.php         -> ek baar chalao, phir delete kar do
└── database.sql
```

## Property Module (NEW)
- **Add Property:** `dashboard/add_property.php` (Admin + Property Officer)
- **Property List:** `dashboard/properties.php` (sabhi roles dekh sakte hain, filter bhi hai)
- **Edit/Delete:** sirf Admin + Property Officer
- Agar aapne pehle se database import kar rakha hai, sirf `update_properties_table.sql`
  ko phpMyAdmin me import kar dena — isse naya `properties` table ban jayega.
- Images `assets/uploads/properties/` folder me save hoti hain.

## Next Steps (aage kya banega)
- Admin se naya user create karne ka page
- Har role ka apna specific workflow (Lottery draw, Auction bidding, FCFS booking)
- Change password feature
- Reports module
