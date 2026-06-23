<?php

use App\Models\EmailPreset;
use App\Support\EmailLayout as E;

return [
    EmailPreset::KEY_LOGIN_OTP => E::wrap(
        E::hero('One-time verification', 'Your login code', 'Use the code below to complete sign-in. It expires soon.')
        .E::greeting()
        .E::paragraph('{{intro}}')
        .E::otpBox()
        .E::expiryBadge('Expires in <strong>{{minutes}} minutes</strong> &mdash; do not share this code')
        .E::divider()
        .E::securityNotice(),
        ['preheader' => 'Your Semrushtoolz verification code — valid for {{minutes}} minutes only.', 'title' => 'Semrushtoolz — Login Code', 'badge' => 'Security'],
    ),

    EmailPreset::KEY_LOGIN_CONFIRMATION => E::wrap(
        E::hero('Account access', 'Confirm your login', 'We noticed a sign-in attempt on your account.')
        .E::greeting()
        .E::paragraph('Use the code below or confirm instantly with the button.')
        .E::otpBox()
        .E::expiryBadge('Code and link expire in <strong>{{minutes}} minutes</strong>')
        .E::button('{{login_url}}', 'Confirm Login')
        .E::divider()
        .E::securityNotice('Wasn\'t you?', 'If this wasn\'t you, ignore this email and consider changing your password.'),
        ['preheader' => 'Confirm your Semrushtoolz login — code expires in {{minutes}} minutes.', 'title' => 'Semrushtoolz — Confirm Login', 'badge' => 'Security'],
    ),

    EmailPreset::KEY_PERIODIC_OTP => E::wrap(
        E::hero('Security check', 'Verification required', 'For your protection, please verify your identity.')
        .E::greeting()
        .E::paragraph('{{intro}}')
        .E::otpBox()
        .E::expiryBadge('Expires in <strong>{{minutes}} minutes</strong>')
        .E::divider()
        .E::securityNotice(),
        ['preheader' => 'Your Semrushtoolz security code — valid for {{minutes}} minutes.', 'title' => 'Semrushtoolz — Security Code', 'badge' => 'Security'],
    ),

    EmailPreset::KEY_SIGNUP_CREDENTIALS => E::wrap(
        E::hero('Welcome aboard', 'Your account is ready', 'Here are your login credentials to get started.')
        .E::greeting()
        .E::detailsList(
            E::detailRow('Email', '{{email}}')
            .E::detailRow('Username', '{{username}}')
            .E::detailRow('Password', '{{password}}')
        )
        .E::button('{{login_url}}', 'Sign In Now')
        .E::highlightBox('Please change your password after your first login for better security.'),
        ['preheader' => 'Your Semrushtoolz account has been created — sign in with the credentials inside.', 'title' => 'Semrushtoolz — Welcome', 'badge' => 'Account'],
    ),

    EmailPreset::KEY_VERIFY_EMAIL => E::wrap(
        E::hero('Email verification', 'Verify your email', 'One quick step to activate your account.')
        .E::greeting()
        .E::paragraph('Thanks for signing up! Please confirm your email address to activate your account and access the dashboard.')
        .E::button('{{verification_url}}', 'Verify Email Address')
        .E::expiryBadge('This link expires in <strong>60 minutes</strong>')
        .E::divider()
        .E::securityNotice(),
        ['preheader' => 'Verify your Semrushtoolz email address to activate your account.', 'title' => 'Semrushtoolz — Verify Email', 'badge' => 'Account'],
    ),

    EmailPreset::KEY_FORGOT_PASSWORD => E::wrap(
        E::hero('Password reset', 'Reset your password', 'We received a request to change your password.')
        .E::greeting()
        .E::paragraph('Click the button below to choose a new password for your Semrushtoolz account.')
        .E::button('{{reset_url}}', 'Reset Password')
        .E::expiryBadge('This link expires in <strong>{{expire_minutes}} minutes</strong>')
        .E::divider()
        .E::securityNotice('Didn\'t request this?', 'If you didn\'t request a password reset, you can safely ignore this email.'),
        ['preheader' => 'Reset your Semrushtoolz password — link expires in {{expire_minutes}} minutes.', 'title' => 'Semrushtoolz — Reset Password', 'badge' => 'Security'],
    ),

    EmailPreset::KEY_SUBSCRIPTION_PAID => E::wrap(
        E::hero('Payment confirmed', 'You\'re all set', 'Your subscription is now active.')
        .E::greeting()
        .E::paragraph('Thank you! Your payment for <strong>{{plan_name}}</strong> has been confirmed.')
        .E::detailsList(
            E::detailRow('Plan', '{{plan_name}}')
            .E::detailRow('Amount', '{{currency}} {{amount}}')
            .E::detailRow('Order', '{{order_id}}')
            .E::detailRow('Valid until', '{{expires_at}}')
        )
        .E::button('{{dashboard_url}}', 'Go to My Tools'),
        ['preheader' => 'Payment confirmed for {{plan_name}} — your access is now active.', 'title' => 'Semrushtoolz — Payment Confirmed', 'badge' => 'Payment'],
    ),

    EmailPreset::KEY_SUBSCRIPTION_PENDING => E::wrap(
        E::hero('Action needed', 'Complete your payment', 'Your selected plan is waiting for checkout.')
        .E::greeting()
        .E::paragraph('You selected <strong>{{plan_name}}</strong> but payment is still pending. Complete checkout to activate your access.')
        .E::detailsList(
            E::detailRow('Plan', '{{plan_name}}')
            .E::detailRow('Amount due', '{{currency}} {{amount}}')
            .E::detailRow('Offer expires', '{{expires_at}}')
        )
        .E::button('{{checkout_url}}', 'Complete Payment'),
        ['preheader' => 'Complete payment for {{plan_name}} to activate your Semrushtoolz access.', 'title' => 'Semrushtoolz — Payment Pending', 'badge' => 'Payment'],
    ),

    EmailPreset::KEY_WALLET_TOPUP_COMPLETED => E::wrap(
        E::hero('Wallet update', 'Top-up successful', 'Funds have been added to your wallet.')
        .E::greeting()
        .E::paragraph('Your wallet top-up is complete. The amount has been credited to your Semrushtoolz Wallet.')
        .E::statBadge('₹{{amount}}', 'Added to wallet')
        .E::detailsList(
            E::detailRow('Order', '{{order_id}}')
            .E::detailRow('New balance', '₹{{wallet_balance}}')
        )
        .E::button('{{wallet_url}}', 'View Wallet'),
        ['preheader' => '₹{{amount}} added to your Semrushtoolz Wallet.', 'title' => 'Semrushtoolz — Wallet Top-up', 'badge' => 'Wallet'],
    ),

    EmailPreset::KEY_WALLET_CASHBACK_RECEIVED => E::wrap(
        E::hero('Cashback earned', 'Money back in your wallet', 'Thanks for your recent purchase.')
        .E::greeting()
        .E::paragraph('Thanks for purchasing <strong>{{purchase_name}}</strong>! We\'ve credited cashback to your wallet.')
        .E::statBadge('₹{{cashback_amount}}', 'Cashback earned')
        .E::detailsList(
            E::detailRow('Order', '{{order_id}}')
            .E::detailRow('Wallet balance', '₹{{wallet_balance}}')
        )
        .E::button('{{wallet_url}}', 'Use Wallet Balance'),
        ['preheader' => '₹{{cashback_amount}} cashback credited to your Semrushtoolz Wallet.', 'title' => 'Semrushtoolz — Cashback', 'badge' => 'Wallet'],
    ),

    EmailPreset::KEY_NEW_TOOL => E::wrap(
        E::hero('New arrival', 'New tool available', 'We just added something new for you.')
        .E::greeting()
        .E::paragraph('Great news! We just added <strong>{{tool_name}}</strong> to Semrushtoolz.')
        .E::highlightBox('{{tool_description}}')
        .E::paragraphTight('Included in your plan: <strong>{{plan_name}}</strong>')
        .E::button('{{tool_url}}', 'Try {{tool_name}}'),
        ['preheader' => '{{tool_name}} is now available on Semrushtoolz.', 'title' => 'Semrushtoolz — New Tool', 'badge' => 'Update'],
    ),

    EmailPreset::KEY_PLAN_EXPIRE_7D => E::wrap(
        E::hero('Renewal reminder', 'Plan expiring in 7 days', 'Don\'t lose access to your tools.')
        .E::greeting()
        .E::paragraph('Your <strong>{{plan_name}}</strong> plan will expire on <strong>{{expires_at}}</strong>. Renew now to keep uninterrupted access.')
        .E::button('{{renew_url}}', 'Renew Now'),
        ['preheader' => 'Your {{plan_name}} plan expires in 7 days — renew to keep access.', 'title' => 'Semrushtoolz — Plan Expiring', 'badge' => 'Renewal'],
    ),

    EmailPreset::KEY_PLAN_EXPIRE_3D => E::wrap(
        E::hero('Urgent reminder', '3 days left on your plan', 'Your access is ending soon.')
        .E::greeting()
        .E::highlightBox('Your <strong>{{plan_name}}</strong> plan expires on <strong>{{expires_at}}</strong> — only <strong>3 days</strong> remaining.', E::WARNING)
        .E::button('{{renew_url}}', 'Renew Now'),
        ['preheader' => 'Only 3 days left on your {{plan_name}} plan.', 'title' => 'Semrushtoolz — 3 Days Left', 'badge' => 'Renewal'],
    ),

    EmailPreset::KEY_PLAN_EXPIRE_2D => E::wrap(
        E::hero('Renewal reminder', '2 days left — renew soon', 'Your plan access is almost over.')
        .E::greeting()
        .E::paragraph('Your <strong>{{plan_name}}</strong> access ends in <strong>2 days</strong> ({{expires_at}}).')
        .E::button('{{renew_url}}', 'Renew Before It Expires'),
        ['preheader' => '2 days left on your {{plan_name}} plan.', 'title' => 'Semrushtoolz — 2 Days Left', 'badge' => 'Renewal'],
    ),

    EmailPreset::KEY_PLAN_EXPIRE_TODAY => E::wrap(
        E::hero('Final notice', 'Your plan expires today', 'This is your last day of access.')
        .E::greeting()
        .E::highlightBox('<strong>Today is the last day</strong> of your <strong>{{plan_name}}</strong> plan ({{expires_at}}). Renew now to avoid losing access.', E::DANGER)
        .E::buttonDanger('{{renew_url}}', 'Renew Today'),
        ['preheader' => 'Your {{plan_name}} plan expires today — renew now.', 'title' => 'Semrushtoolz — Expires Today', 'badge' => 'Renewal'],
    ),

    EmailPreset::KEY_PLAN_EXPIRED_1D => E::wrap(
        E::hero('Plan expired', 'Your access ended yesterday', 'Renew to restore your tools.')
        .E::greeting()
        .E::paragraph('Your <strong>{{plan_name}}</strong> plan expired yesterday ({{expired_at}}). Renew now to restore access.')
        .E::button('{{renew_url}}', 'Reactivate Plan'),
        ['preheader' => 'Your {{plan_name}} plan expired — renew to restore access.', 'title' => 'Semrushtoolz — Plan Expired', 'badge' => 'Renewal'],
    ),

    EmailPreset::KEY_PLAN_EXPIRED_2D => E::wrap(
        E::hero('We miss you', 'Come back anytime', 'Your tools are waiting for you.')
        .E::greeting()
        .E::paragraph('It has been 2 days since your <strong>{{plan_name}}</strong> plan expired ({{expired_at}}). Come back and pick up where you left off.')
        .E::button('{{renew_url}}', 'Come Back — Renew Now'),
        ['preheader' => 'Your {{plan_name}} expired 2 days ago — we\'d love to have you back.', 'title' => 'Semrushtoolz — We Miss You', 'badge' => 'Renewal'],
    ),

    EmailPreset::KEY_PLAN_EXPIRED_3D => E::wrap(
        E::hero('Access paused', '3 days without access', 'Reactivate your plan anytime.')
        .E::greeting()
        .E::paragraph('Your <strong>{{plan_name}}</strong> has been inactive for 3 days since {{expired_at}}.')
        .E::button('{{renew_url}}', 'Restore Access'),
        ['preheader' => '3 days since your {{plan_name}} plan expired.', 'title' => 'Semrushtoolz — Restore Access', 'badge' => 'Renewal'],
    ),

    EmailPreset::KEY_PLAN_EXPIRED_1W => E::wrap(
        E::hero('Special offer', '1 week since expiry', 'We have a renewal offer for you.')
        .E::greeting()
        .E::paragraph('It has been a week since your <strong>{{plan_name}}</strong> expired ({{expired_at}}).')
        .E::highlightBox('Use code <strong>{{offer_code}}</strong> for a special renewal offer.')
        .E::button('{{renew_url}}', 'Renew with Offer'),
        ['preheader' => 'Special renewal offer for your {{plan_name}} plan.', 'title' => 'Semrushtoolz — Renewal Offer', 'badge' => 'Offer'],
    ),

    EmailPreset::KEY_PLAN_EXPIRED_2W => E::wrap(
        E::hero('Exclusive offer', 'We\'d love to have you back', 'Two weeks since your plan ended.')
        .E::greeting()
        .E::paragraph('Two weeks ago your <strong>{{plan_name}}</strong> plan ended ({{expired_at}}).')
        .E::highlightBox('Exclusive renewal code: <strong>{{offer_code}}</strong>')
        .E::button('{{renew_url}}', 'Rejoin Semrushtoolz'),
        ['preheader' => 'Exclusive renewal code for your {{plan_name}} plan.', 'title' => 'Semrushtoolz — Come Back', 'badge' => 'Offer'],
    ),

    EmailPreset::KEY_PROMO_OFFER_1 => E::wrap(
        E::hero('Special offer', '{{subject_line}}', 'A limited-time deal just for you.')
        .E::greeting()
        .E::paragraph('{{message}}')
        .E::button('{{cta_url}}', '{{cta_label}}'),
        ['preheader' => '{{subject_line}}', 'title' => 'Semrushtoolz — Special Offer', 'badge' => 'Promo'],
    ),

    EmailPreset::KEY_PROMO_OFFER_2 => E::wrap(
        E::hero('Exclusive deal', '{{subject_line}}', 'Use your offer code below.')
        .E::greeting()
        .E::paragraph('{{message}}')
        .E::statBadge('{{offer_code}}', 'Your offer code')
        .E::button('{{cta_url}}', 'Claim Offer'),
        ['preheader' => '{{subject_line}} — code inside.', 'title' => 'Semrushtoolz — Offer Code', 'badge' => 'Promo'],
    ),

    EmailPreset::KEY_PROMO_OFFER_3 => E::wrap(
        E::hero('Limited time', '{{subject_line}}', 'Don\'t miss this discount.')
        .E::greeting()
        .E::paragraph('{{message}}')
        .E::statBadge('{{discount_percent}}% OFF', 'Limited time')
        .E::button('{{cta_url}}', 'Get Discount'),
        ['preheader' => '{{subject_line}} — {{discount_percent}}% off inside.', 'title' => 'Semrushtoolz — Discount', 'badge' => 'Promo'],
    ),

    EmailPreset::KEY_REFERRAL_BONUS_DAY1 => E::wrap(
        E::hero('Referral bonus', 'Your bonus is waiting', 'Extra discount on your first purchase.')
        .E::greeting()
        .E::paragraph('Welcome! You signed up through a referral link, so you get <strong>{{discount_percent}}% extra off</strong> your first <strong>1-month</strong> purchase.')
        .E::highlightBox('Valid for <strong>{{days_left}} more days</strong> (until {{expires_at}}). Stackable with coupon codes.')
        .E::button('{{shop_url}}', 'Shop Plans Now'),
        ['preheader' => '{{discount_percent}}% referral bonus — valid for {{days_left}} more days.', 'title' => 'Semrushtoolz — Referral Bonus', 'badge' => 'Bonus'],
    ),

    EmailPreset::KEY_REFERRAL_BONUS_DAY2 => E::wrap(
        E::hero('Bonus reminder', 'Your discount ends soon', 'Don\'t let your referral bonus expire.')
        .E::greeting()
        .E::paragraph('Your referral signup bonus is still active — <strong>{{discount_percent}}% off</strong> your first 1-month plan.')
        .E::expiryBadge('Only <strong>{{days_left}} days</strong> left (until {{expires_at}})')
        .E::button('{{shop_url}}', 'Claim Your Discount'),
        ['preheader' => 'Referral bonus ends in {{days_left}} days.', 'title' => 'Semrushtoolz — Bonus Reminder', 'badge' => 'Bonus'],
    ),

    EmailPreset::KEY_REFERRAL_BONUS_DAY3 => E::wrap(
        E::hero('Last chance', 'Bonus expires today', 'Final day for your referral discount.')
        .E::greeting()
        .E::highlightBox('<strong>Last chance!</strong> Your {{discount_percent}}% referral bonus expires on <strong>{{expires_at}}</strong>.', E::DANGER)
        .E::paragraphTight('Extra discount on your first 1-month purchase — stackable with coupon codes.')
        .E::buttonDanger('{{shop_url}}', 'Buy Before It Expires'),
        ['preheader' => 'Last day — {{discount_percent}}% referral bonus expires today.', 'title' => 'Semrushtoolz — Last Chance', 'badge' => 'Bonus'],
    ),

    EmailPreset::KEY_AFFILIATE_MONTHLY_REPORT => E::wrap(
        E::hero('Monthly report', 'Affiliate earnings', 'Your summary for {{period_label}}.')
        .E::greeting()
        .E::paragraph('Here is your affiliate summary for <strong>{{period_label}}</strong> (as of {{as_of}}).')
        .E::detailsList(
            E::detailRow('Earnings this month', '{{month_earnings}}')
            .E::detailRow('Payouts processed', '{{month_payouts}}')
            .E::detailRow('Carried balance', '{{carried_balance}}')
            .E::detailRow('On hold (PayPal)', '{{held_amount}}')
            .E::detailRow('Available for payout', '<span style="color:'.E::ACCENT.';">{{total_payable}}</span>')
        )
        .E::muted('{{paypal_hold_note}}')
        .E::button('{{affiliates_url}}', 'View Affiliate Dashboard'),
        ['preheader' => 'Your affiliate earnings report for {{period_label}}.', 'title' => 'Semrushtoolz — Affiliate Report', 'badge' => 'Affiliate'],
    ),

    EmailPreset::KEY_AFFILIATE_PROGRAM_INVITE => E::wrap(
        E::hero('Affiliate program', 'Earn {{commission_rate}}% commission', 'Share Semrushtoolz and get paid.')
        .E::greeting()
        .E::paragraph('{{program_intro}}')
        .E::highlightBox('{{signup_bonus_note}}')
        .E::paragraphTight('Your personal referral link:')
        .E::codeBlock('{{referral_link}}')
        .E::paragraphTight('Your code: <strong>{{referral_code}}</strong>')
        .E::button('{{affiliates_url}}', 'Open Affiliate Dashboard'),
        ['preheader' => 'Join the Semrushtoolz affiliate program — earn {{commission_rate}}% per sale.', 'title' => 'Semrushtoolz — Affiliate Invite', 'badge' => 'Affiliate'],
    ),

    EmailPreset::KEY_AFFILIATE_PROGRAM_BOOST => E::wrap(
        E::hero('Affiliate update', '{{month_label}} snapshot', 'See how your earnings are growing.')
        .E::greeting()
        .E::paragraph('Here is how your affiliate earnings look this month:')
        .E::detailsList(
            E::detailRow('Earnings this month', '{{month_earnings}}')
            .E::detailRow('Total earned', '{{total_earned}}')
            .E::detailRow('Available for payout', '<span style="color:'.E::ACCENT.';">{{total_payable}}</span>')
        )
        .E::highlightBox('{{growth_tip}}')
        .E::paragraphTight('Your referral link: <a href="{{referral_link}}" style="color:'.E::ACCENT.';text-decoration:none;font-weight:600;">{{referral_link}}</a>')
        .E::button('{{affiliates_url}}', 'View Reports &amp; Share Link'),
        ['preheader' => 'Your {{month_label}} affiliate earnings snapshot.', 'title' => 'Semrushtoolz — Affiliate Update', 'badge' => 'Affiliate'],
    ),
];
