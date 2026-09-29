---
title: WordPress Security Checklist: 15 Steps to Protect Your Website in 2026
slug: wordpress-security-checklist
focus_keyword: WordPress security checklist
cover_title: WordPress Security Checklist
seo_title: WordPress Security Checklist: 15 Steps for 2026
meta_description: A practical WordPress security checklist: updates, firewall, backups, login protection, spam protection, file permissions, and malware scans in 15 steps.
excerpt: A practical 15-step WordPress security checklist covering updates, firewalls, backups, login protection, spam, hosting, and what to do if your site is hacked.
category: Performance and Security
tags: WordPress security, website security, Wordfence, website backup, spam protection, malware removal
---

This WordPress security checklist covers the steps I apply to client websites. WordPress core is well maintained, and most hacked sites are not hacked through WordPress itself, but through outdated plugins, weak passwords, and missing backups. Every step here is practical, and most take minutes.

## Updates and Software

### 1. Keep Everything Updated

Update WordPress core, themes, and plugins regularly, after a backup. Many updates close known security holes.

### 2. Remove What You Do Not Use

Delete inactive plugins and themes. Even deactivated code can be a risk if it has a vulnerability.

### 3. Use Trusted Sources Only

Install plugins and themes only from WordPress.org, the official developer, or reputable marketplaces. Nulled premium plugins often contain malware.

## Login and Users

### 4. Strong Passwords and Unique Accounts

Every user gets their own account with a strong, unique password. No shared "admin" logins.

### 5. Two-Factor Authentication

Enable two-factor login for all administrators and shop managers.

### 6. Limit Login Attempts

Block repeated failed logins to stop brute-force attacks.

### 7. Correct User Roles

Give each person the lowest role they need. An editor does not need administrator access.

## Firewall and Monitoring

### 8. Use a Web Application Firewall

A firewall blocks malicious traffic before it reaches your site. Options include a security plugin like Wordfence or protection at the CDN or server level, such as Cloudflare. The right choice depends on your hosting and traffic.

### 9. Scan for Malware

Scheduled malware scans catch injected code, suspicious files, and changed core files early.

### 10. Monitor Uptime and Activity

Uptime alerts and an activity log show when the site goes down and who changed what.

## Backups

### 11. Automatic Off-Site Backups

Schedule backups of files and database with tools like UpdraftPlus or your host's backup system, stored off-site in cloud storage. Keep several restore points.

### 12. Test Restores

A backup you have never restored is a hope, not a plan. Test a restore on a staging site.

## Hosting and Configuration

### 13. Quality Hosting, SSL, and Current PHP

Choose hosting with server-level security, use SSL everywhere, and run a supported PHP version.

### 14. Harden WordPress

- Disable file editing from the dashboard.
- Set correct file permissions.
- Protect wp-config.php.
- Restrict REST API endpoints that list users.
- Hide detailed error messages on the live site.

## Forms and Spam

### 15. Protect Forms From Spam

Use honeypot fields, invisible challenges like Cloudflare Turnstile or reCAPTCHA, and spam filters on contact forms, comments, and registrations. More in [dynamic forms in WordPress](/dynamic-forms-wordpress/).

## If Your Site Is Hacked

1. Put the site in maintenance mode if customers are affected.
2. Change all passwords: WordPress, hosting, database, and FTP.
3. Scan and remove malware, or restore a clean backup.
4. Update everything and remove the vulnerable plugin.
5. Request a review in Google Search Console if warnings appear.

## Frequently Asked Questions

### Is WordPress secure?

Yes, when maintained. Most breaches come from outdated plugins and weak passwords, not WordPress core.

### Do I need a security plugin?

Most sites benefit from one, or from equivalent protection at the host or CDN level.

### Can you clean a hacked site?

Yes. I remove malware, close the entry point, and harden the site to prevent it happening again.

## Want Your Site Secured?

I handle security hardening, malware removal, and ongoing monitoring. [Contact me here](/contact/). For ongoing care, see [WordPress website maintenance cost](/wordpress-maintenance-cost/).
