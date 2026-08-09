# Corvin VPN — Landing Page + Payment & Key Delivery System

A complete client-side system for a commercial VPN service.

**Live website:** [korvinvpn.com](https://korvinvpn.com)

---

## About the Project

Corvin VPN is a production VPN service focused on providing reliable access to blocked resources.

### My contribution
I developed the entire client-facing part of the product:

- Modern responsive landing page
- Tariff selection pages with smooth animations
- Payment system integration
- Automatic key delivery via email after successful payment
- Detailed setup instructions page
- Public offer (legal agreement) page

### Partner contribution
VPN infrastructure, Remnawave panel management and key generation are handled by [NexOff](https://github.com/NexOff12).

---

## Features

### Frontend
- Fully responsive design (desktop + mobile)
- Smooth animations and modern dark UI
- Pages: Home, Tariffs, Setup Instructions, Success, Public Offer
- FAQ accordion

### Backend & Payments
- PHP payment creation
- Payment result handling
- Automatic sending of unique VPN key + setup instructions to the user’s email
- Order logging

### Integrations
- Payment gateway (Robokassa / YooKassa)
- Email delivery of keys
- Remnawave (VPN key and server management)

---

## Tech Stack

| Layer              | Technologies                          |
|--------------------|---------------------------------------|
| Frontend           | HTML5, CSS3, Vanilla JavaScript       |
| Backend            | PHP                                   |
| Payments           | Robokassa / YooKassa                  |
| VPN Panel          | Remnawave                             |
| Protocols          | VLESS, Hysteria, Trojan               |

---

## Project Structure
```bash
├── index.html              # Main landing page
├── tariff.html             # Tariff selection
├── setup.html              # Setup instructions
├── success.html            # Successful payment page
├── oferta.html             # Public offer (legal)
├── pay.php                 # Create payment
├── result.php              # Handle payment result + deliver key
├── styles.css
├── script.js
├── tariff.js
├── setup.js
└── assets/                 # Images, icons, video
```
---

## Important Notes

- All sensitive data (payment gateway secrets, SMTP credentials, Remnawave API keys) have been removed and replaced with placeholders.
- Actual VPN key generation and server management are handled via the Remnawave panel by the partner.
- This project has been running in production.

---

## Local Setup

1. Place the files on any PHP-enabled server (or use `php -S localhost:8000`)
2. Replace placeholder credentials in `pay.php` and `result.php`
3. Open `index.html`

---

## Screenshots

---
## Authors

- **[Alexander Busygin](https://github.com/Endropr)** — Client-side development, landing page, payment system, key delivery logic
- **[NexOff](https://github.com/NexOff12)** — Remnawave infrastructure, server management and key generation
