<?php

namespace Novatio\SecurityHardener\Email;

use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;

class LockedOutNotification extends Email
{
    /**
     * LockedOutNotification constructor.
     *
     * @param null $from
     * @param null $to
     * @param null $subject
     * @param null $body
     * @param null $bounceHandlerURL
     * @param null $cc
     * @param null $bcc
     */
    public function __construct($from = null,
        $to = null,
        $subject = null,
        $body = null,
        $bounceHandlerURL = null,
        $cc = null,
        $bcc = null)
    {
        $subject = $subject ?: _t('LockedOutNotification.subject', '[{site}] Member locked out after incorrect logins', [
            'site' => SiteConfig::current_site_config()->Title
        ]);

        $this->setHTMLTemplate('email\\LockedOutNotification');

        parent::__construct($from, $to, $subject, $body, $bounceHandlerURL, $cc, $bcc);
    }
}
