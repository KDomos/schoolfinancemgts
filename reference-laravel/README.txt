SFMS server: simple and secure setup

What you need
A fresh Ubuntu 24.04 server (a 2 GB VPS is enough) and a domain or subdomain whose A record already points to the server's IP address.

Setup (about 10 minutes)
1. Copy this folder to the server (for example: scp -r sfms-laravel you@SERVER_IP:~/) and log in with SSH.
2. Run:  cd sfms-laravel && sudo bash deploy.sh yourdomain.com
3. Read /root/sfms-secrets.txt (sudo cat /root/sfms-secrets.txt). Give your payment gateway the webhook URL and its matching secret.

What the script does for you
Installs the web server (Caddy, which gets and renews the HTTPS certificate by itself), PHP and MariaDB. Creates a private database user with a random password. Turns off debug mode. Generates a separate random signing secret for each gateway. Blocks every port except SSH, HTTP and HTTPS. Turns on automatic security updates and brute-force blocking. Takes a nightly database backup into /var/backups/sfms and keeps 14 days.

Test it
After deploy, add a student and a fee in the database, then send the curl test from the earlier notes using the secret from sfms-secrets.txt. A correct payment returns "posted", a repeat returns "duplicate", and an unknown code returns "held".

Your two jobs
Copy the backups off the server now and then (for example to your computer with scp). Adjust normalise() in PaymentWebhookController.php once your gateway gives you its real payload and signature rules.

Not built yet in the server version: bursar login, the payment forms and dashboards, PDF receipts and SMS. The published web page shows how those screens will work.

Logins
deploy.sh creates four accounts with random passwords: bursar, director, deputy and auditor (for example bursar@yourdomain.com). They are written to /root/sfms-logins.txt, readable by root only. Sign in at https://yourdomain.com/login, then change each password and delete that file.
Passwords are stored hashed. Sessions end after 30 minutes idle, cookies are HTTPS-only, and sign-in is limited to 5 attempts a minute.
Roles are checked on the server for every protected page (RoleMiddleware). The audit_log table records sign-ins, failed sign-ins and received payments, and the database refuses any edit or delete of its rows.
