<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disposable / temporary email blocklist
    |--------------------------------------------------------------------------
    |
    | Domains from public disposable-email lists plus common temp-mail providers.
    | Subdomains of these domains are also blocked.
    |
    */

    'disposable_domains' => [
        '0-mail.com', '10minutemail.com', '10minutemail.net', '20minutemail.com',
        '33mail.com', 'anonbox.net', 'anonymbox.com', 'binkmail.com', 'bobmail.info',
        'boun.cr', 'brefmail.com', 'bugmenot.com', 'burnermail.io', 'byom.de',
        'centermail.com', 'chammy.info', 'cock.li', 'crazymailing.com', 'cuvox.de',
        'dayrep.com', 'discard.email', 'discardmail.com', 'discardmail.de',
        'disposableaddress.com', 'disposablemail.com', 'dispostable.com',
        'dodgit.com', 'dropmail.me', 'duck.com', 'einrot.com', 'emailondeck.com',
        'emailtemporario.com.br', 'emailtmp.com', 'emkei.cz', 'emlhub.com',
        'emlpro.com', 'emltmp.com', 'ephemail.net', 'explodemail.com',
        'fakeinbox.com', 'fakemailgenerator.com', 'filzmail.com', 'flymail.tk',
        'getairmail.com', 'getnada.com', 'ghosttexter.de', 'gishpuppy.com',
        'guerrillamail.com', 'guerrillamail.net', 'guerrillamail.org',
        'guerrillamailblock.com', 'harakirimail.com', 'hidemail.de', 'hushmail.com',
        'inboxalias.com', 'inboxbear.com', 'incognitomail.com', 'incognitomail.org',
        'jetable.com', 'jetable.fr.nf', 'jetable.net', 'jetable.org',
        'kasmail.com', 'killmail.com', 'killmail.net', 'koszmail.pl',
        'kurzepost.de', 'letthemeatspam.com', 'lhsdv.com', 'litedrop.com',
        'lookugly.com', 'lroid.com', 'mail-temp.com', 'mail-temporaire.fr',
        'mail1a.de', 'mailcatch.com', 'maildrop.cc', 'maildrop.cf',
        'mailforspam.com', 'mailguard.me', 'mailhazard.com', 'mailhazard.us',
        'mailimate.com', 'mailin8r.com', 'mailinator.com', 'mailinator.net',
        'mailinator.org', 'mailinator2.com', 'mailme.lv', 'mailmetrash.com',
        'mailmoat.com', 'mailnesia.com', 'mailnull.com', 'mailpick.biz',
        'mailscrap.com', 'mailshell.com', 'mailsiphon.com', 'mailtemp.info',
        'mailtome.de', 'mailtothis.com', 'mailzilla.com', 'mailzilla.org',
        'mbx.cc', 'meltmail.com', 'messagebeamer.de', 'mintemail.com',
        'mohmal.com', 'mohmal.im', 'mohmal.in', 'moncourrier.fr.nf',
        'monemail.fr.nf', 'monmail.fr.nf', 'my10minutemail.com', 'mytemp.email',
        'mytrashmail.com', 'nada.email', 'nada.ltd', 'nepwk.com',
        'nobulk.com', 'noclickemail.com', 'nogmailspam.info', 'nomail.pw',
        'nomail.xl.cx', 'nomail2me.com', 'nospam.ze.tc', 'nospam4.us',
        'nospamfor.us', 'nospammail.net', 'notmailinator.com', 'nowmymail.com',
        'objectmail.com', 'oneoffemail.com', 'onewaymail.com', 'opayq.com',
        'otherinbox.com', 'pancakemail.com', 'pookmail.com', 'proxymail.eu',
        'rcpt.at', 're-gister.com', 'receiveee.com', 'recyclemail.dk',
        'rmqkr.net', 'rppkn.com', 'rtrtr.com', 's0ny.net', 'safe-mail.net',
        'sharklasers.com', 'shieldemail.com', 'shiftmail.com', 'shitmail.me',
        'shortmail.net', 'sibmail.com', 'sneakemail.com', 'snkmail.com',
        'sogetthis.com', 'soodonims.com', 'spam.la', 'spam4.me', 'spamail.de',
        'spambob.com', 'spambob.net', 'spambog.com', 'spambog.de', 'spambog.ru',
        'spambox.us', 'spamcero.com', 'spamcorptastic.com', 'spamcowboy.com',
        'spamcowboy.net', 'spamcowboy.org', 'spamday.com', 'spamex.com',
        'spamfree24.com', 'spamfree24.de', 'spamfree24.eu', 'spamfree24.info',
        'spamfree24.net', 'spamfree24.org', 'spamgourmet.com', 'spamgourmet.net',
        'spamgourmet.org', 'spamherelots.com', 'spamhereplease.com', 'spamhole.com',
        'spamify.com', 'spaml.com', 'spaml.de', 'spammotel.com', 'spamobox.com',
        'spamspot.com', 'spamthis.co.uk', 'spamthisplease.com', 'spamtrail.com',
        'spamtroll.net', 'superrito.com', 'suremail.info', 'teleworm.us',
        'temp-mail.org', 'temp-mail.ru', 'tempail.com', 'tempe-mail.com',
        'tempemail.biz', 'tempemail.co.za', 'tempemail.com', 'tempemail.net',
        'tempinbox.co.uk', 'tempinbox.com', 'tempmail.co', 'tempmail.de',
        'tempmail.it', 'tempmail.net', 'tempmail.ninja', 'tempmail.plus',
        'tempmail.us', 'tempmail2.com', 'tempmaildemo.com', 'tempmailer.com',
        'tempmailer.de', 'tempmailo.com', 'tempomail.fr', 'temporarily.de',
        'temporarioemail.com.br', 'temporaryemail.net', 'temporaryemail.us',
        'temporaryforwarding.com', 'temporaryinbox.com', 'temporarymailaddress.com',
        'tempr.email', 'tempsky.com', 'tempthe.net', 'tempymail.com',
        'thanksnospam.info', 'thankyou2010.com', 'thisisnotmyrealemail.com',
        'throwawayemailaddress.com', 'throwawaymail.com', 'tilien.com',
        'tmail.ws', 'tmailinator.com', 'toiea.com', 'trash-amil.com',
        'trash-mail.at', 'trash-mail.com', 'trash-mail.de', 'trash2009.com',
        'trashemail.de', 'trashmail.at', 'trashmail.com', 'trashmail.de',
        'trashmail.me', 'trashmail.net', 'trashmail.org', 'trashmail.ws',
        'trashmailer.com', 'trashymail.com', 'trashymail.net', 'trbvm.com',
        'trialmail.de', 'turual.com', 'twinmail.de', 'tyldd.com',
        'uggsrock.com', 'upliftnow.com', 'uplipht.com', 'venompen.com',
        'veryrealemail.com', 'viditag.com', 'viralplays.com', 'vpn.st',
        'vsimcard.com', 'vubby.com', 'walala.org', 'walkmail.net',
        'webemail.me', 'weg-werf-email.de', 'wegwerf-email-addressen.de',
        'wegwerf-emails.de', 'wegwerfadresse.de', 'wegwerfemail.com',
        'wegwerfemail.de', 'wegwerfemail.net', 'wegwerfemail.org',
        'wegwerfemailadresse.com', 'wegwerfmail.de', 'wegwerfmail.info',
        'wegwerfmail.net', 'wegwerfmail.org', 'wetrainbayarea.com',
        'wh4f.org', 'whyspam.me', 'willselfdestruct.com', 'winemaven.info',
        'wronghead.com', 'wuzup.net', 'wuzupmail.net', 'xoxy.net',
        'yep.it', 'yogamaven.com', 'yopmail.com', 'yopmail.fr', 'yopmail.net',
        'yopmail.pp.ua', 'ypmail.webarnak.fr.eu.org', 'zippymail.info',
        'zoemail.org', 'zomg.info',         'mail.tm', 'mail.gw',
    ],

    /*
    | High-risk TLDs commonly used by throwaway mail services.
    | Legitimate users on these TLDs are rare for a SaaS signup.
    */
    'blocked_tlds' => [
        '.tk', '.ml', '.ga', '.cf', '.gq', '.bond', '.icu', '.cam',
    ],

    /*
    | Substrings in the domain that strongly suggest disposable mail.
    */
    'disposable_keywords' => [
        'tempmail', 'tmpmail', 'trashmail', 'fakeinbox', 'throwaway',
        'disposable', 'mailinator', 'guerrillamail', 'minuteinbox',
        'temp-mail', 'tempemail', 'spambox', 'getnada', 'mohmal',
        'emailfake', 'fakemail', 'burner', 'discard', 'maildrop',
    ],

    'blocked_patterns' => [
        '/@(temp|trash|disposable|throwaway|fake|spam|guerrilla|minute|minut)[a-z0-9\-]*\./i',
        '/@(mailinator|yopmail|tempmail|getnada|sharklasers|maildrop|mailnesia)\./i',
        '/@(tempmail|tmpmail|trashmail|fakeinbox|burnermail|discardmail)[a-z0-9\-]*\./i',
        '/^[a-z0-9._\-]{0,2}(\.[a-z0-9._\-]+){4,}@/i',
        '/^[0-9]{6,}@/i',
    ],

];
