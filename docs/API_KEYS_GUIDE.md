# 🔑 API Keys Configuration Guide

## Overview
This document provides guidance on configuring external API integrations for ZenithaLMS.

---

## Required API Keys

### 1. Payment Integration

#### Stripe (Recommended)
```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

**How to get:**
1. Go to https://dashboard.stripe.com/register
2. Create account and verify email
3. Navigate to Developers → API Keys
4. Copy "Publishable key" and "Secret key"
5. For webhooks: Developers → Webhooks → Add endpoint

**Test Cards:**
- Success: `4242 4242 4242 4242`
- Decline: `4000 0000 0000 0002`
- Requires Auth: `4000 0025 0000 3155`

#### PayPal
```env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=...
PAYPAL_CLIENT_SECRET=...
```

**How to get:**
1. Go to https://developer.paypal.com
2. Create app in Dashboard
3. Copy Client ID and Secret
4. Use sandbox for testing

---

### 2. AI Integration (Optional)

#### OpenAI
```env
OPENAI_API_KEY=sk-...
OPENAI_ORGANIZATION=org-...
```

**How to get:**
1. Go to https://platform.openai.com/signup
2. Navigate to API Keys
3. Create new secret key
4. Copy and save immediately (shown once)

**Features enabled:**
- AI Course Recommendations
- Adaptive Learning Paths
- AI Assistant Chat
- Content Generation

**Cost:** Pay-as-you-go (starts at $0.002/1K tokens)

---

### 3. Email Service

#### SMTP (Gmail example)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="ZenithaLMS"
```

**How to get Gmail App Password:**
1. Enable 2-Factor Authentication on Google Account
2. Go to https://myaccount.google.com/apppasswords
3. Generate new app password
4. Use that password in MAIL_PASSWORD

**Features enabled:**
- Email verification
- Password reset
- Course enrollment notifications
- Certificate delivery

---

### 4. Real-time Features (Optional)

#### Pusher
```env
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=mt1
```

**How to get:**
1. Go to https://pusher.com/signup
2. Create new Channels app
3. Copy credentials from App Keys tab

**Features enabled:**
- Real-time notifications
- Live chat
- Virtual classroom features

---

## Environment Setup

### Development (.env)
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:3000

# Use test/sandbox keys
STRIPE_KEY=pk_test_...
PAYPAL_MODE=sandbox
```

### Production (.env.production)
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Use live keys
STRIPE_KEY=pk_live_...
PAYPAL_MODE=live
```

---

## Testing Without API Keys

If you don't have API keys yet, the system will:
- ✅ Work for all core features (courses, users, enrollments)
- ⚠️ Disable payment processing (show "Contact Admin")
- ⚠️ Disable AI features (show placeholder)
- ⚠️ Queue emails for later sending

---

## Security Best Practices

### DO:
✅ Use environment variables (.env)
✅ Use test/sandbox keys in development
✅ Rotate keys periodically
✅ Use webhook signing secrets
✅ Enable 2FA on all provider accounts

### DON'T:
❌ Commit .env to Git
❌ Share API keys in screenshots
❌ Use production keys in development
❌ Store keys in code files
❌ Use same keys across projects

---

## Cost Estimates

| Service | Free Tier | Paid Plans |
|---------|-----------|------------|
| Stripe | Free (2.9% + $0.30 per transaction) | No monthly fee |
| PayPal | Free (2.9% + $0.30 per transaction) | No monthly fee |
| OpenAI | $5 free credit | Pay-as-you-go (~$0.02/day) |
| Pusher | 200K messages/day | From $49/month |
| SendGrid | 100 emails/day | From $15/month |

**Recommendation for MVP:** Start with free tiers ($0-5/month total)

---

## Troubleshooting

### "Stripe key invalid"
- Check that you're using the correct key (test vs live)
- Ensure no extra spaces in .env
- Run `php artisan config:clear`

### "OpenAI quota exceeded"
- Check usage at https://platform.openai.com/usage
- Add payment method or upgrade plan

### "Email not sending"
- Test SMTP connection: `php artisan tinker` then `Mail::raw('test', fn($m) => $m->to('test@test.com')->subject('Test'));`
- Check firewall allows port 587/465
- Verify Gmail app password (not regular password)

---

## Support

If you need help:
1. Check logs: `storage/logs/laravel.log`
2. Test individual services in `tinker`
3. Consult provider documentation
4. Contact: support@zenithalms.test

---

**Last Updated:** 2026-03-11  
**Version:** 1.0
