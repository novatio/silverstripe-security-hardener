<?php

namespace Novatio\SecurityHardener\Form;

use SilverStripe\Security\Member;

class ChangePasswordForm extends \SilverStripe\Security\MemberAuthenticator\ChangePasswordForm
{
    // TODO: moved to ChangePasswordHandler?
    public function doChangePassword(array $data)
    {
        $backURL = $this->controller->Link('login');
        $_REQUEST['BackURL'] = $backURL;
        $loggedIn = Member::currentUser();
        parent::doChangePassword($data);
        if (!$loggedIn) {
            $member = Member::currentUser();
            if ($member && $member->Has2FA) {
                $member->logOut();
                $form = $this->controller->LoginForm();
                $form->sessionMessage(
                    'Password successfully changed. Please login.', 'good'
                );
            }
        }
    }
}