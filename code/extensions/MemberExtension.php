<?php

namespace SecurityHardener\Extensions;

use SecurityHardener\Emails\LockedOutNotification;

class MemberExtension extends \DataExtension
{
    /**
     * @var array
     */
    private static $db = [
        'LockedOutNotificationSent' => 'Boolean',
    ];

    /**
     * Send an email (if configured) on lockout.
     */
    public function registerFailedLogin()
    {
        if ($this->owner->isLockedOut() &&
            !$this->owner->LockedOutNotificationSent &&
            ($siteConfig = \SiteConfig::current_site_config()) &&
            $siteConfig->LockoutNotificationEmail
        ) {
            $mail = LockedOutNotification::create(
                $from = null,
                $to = $siteConfig->LockoutNotificationEmail
            )->populateTemplate([
                'Title'  => _t('LockedOutNotification.title', 'Member locked out'),
                'Member' => $this->owner,
            ]);

            $this->owner->LockedOutNotificationSent = (boolean)$mail->send();
        }
    }

    /**
     * Clear LockedOutNotificationSent on login
     */
    public function memberLoggedIn()
    {
        // Don't set column if its not built yet (the login might be precursor to a /dev/build...)
        if (array_key_exists('LockedOutNotificationSent', \DB::field_list('Member'))) {
            $this->owner->LockedOutNotificationSent = null;
            $this->owner->write();
        }
    }

    /**
     * @return \DataObject
     */
    public function getLastFailedLoginAttempt()
    {
        return \LoginAttempt::get()->filter([
            'Status'   => 'Failure',
            'MemberID' => $this->owner->ID
        ])->last();
    }
}