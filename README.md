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

## Property Module
- **Add Property:** `dashboard/add_property.php` (Admin + Property Officer) — fields: Scheme Name, Property No, Property ID (unique), Address, Area, Price, Category, Image
- **Property List:** `dashboard/properties.php` — user ko sirf wahi categories dikhengi jinki use admin ne permission di hai
- **Property Detail:** `dashboard/property_detail.php` — poori detail + allottee info ek jagah
- **Edit/Delete:** sirf Admin + Property Officer
- **Import from Excel/CSV:** `dashboard/import_properties.php` — Excel file ko pehle CSV me save karke upload karo. Sample template link page ke andar hi milega.

## Allottee Module (jab property allot ho jaye)
- `dashboard/add_allottee.php` — Allottee Name, Father's Name, Mobile, Aadhar No, PAN No, Address,
  Aadhar Photo, PAN Photo, Allotment Date. Property Detail page se yaha jaya ja sakta hai.
- Allottee add karte hi property ka status apne aap "Allotted" ho jata hai.

## Permission System (kaun kya dekh sakta hai)
- Admin ko hamesha **sab kuch** dikhta hai (Lottery, Auction, FCFS, Direct Allotment — sab).
- Baaki sabhi roles (Property Officer, LDA, UDC, SO) ke liye Admin decide karta hai
  ki unko kaunsi category dikhegi — ye `dashboard/manage_users.php` se set hota hai
  (naya user banate waqt ya `edit_permissions.php` se baad me bhi badla ja sakta hai).
- Agar kisi user ko koi permission nahi di gayi, to use property list access nahi milega
  jab tak Admin permission na de.

## Database Update (agar pehle se purana database.sql import kiya tha)
`update_v2.sql` file ko phpMyAdmin me import karein — isse:
- `properties` table naye structure me convert hogi (agar isme purana data tha to pehle backup le lein)
- `allottees` aur `user_permissions` naye tables ban jayenge

## Next Steps (aage kya banega)
- Change Password feature
- Lottery draw process, Auction bidding, FCFS booking queue
- Reports / Export module
