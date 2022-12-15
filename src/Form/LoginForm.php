<?php

namespace Novatio\SecurityHardener\Form;

use Novatio\Helpers\Service\SiteHelper;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Session;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;

class LoginForm extends MemberLoginForm
{
    private static $allowed_actions = [ 'cancel' ];

    /**
     * Action to unset the TOTP.ID session var to allow going back to the normal (email/pw) login form
     */
    public function cancel()
    {
        \Novatio\Helpers\Service\SiteHelper::Session()->clear('TOTP.ID');
        Controller::curr()->redirectBack();
    }

    public function doLogin($data)
    {
        $authenticator = Injector::inst()->get(MemberAuthenticator::class);
        $loginHandler = $authenticator->getLoginHandler($this->getRequestHandler()->Link());
        $redirect = isset($_REQUEST['BackURL']) ? $_REQUEST['BackURL'] : (SiteHelper::Session()->get('BackURL') ?? "/");

        if (\Novatio\Helpers\Service\SiteHelper::Session()->get('TOTP.ID')) {
            // Figure out what to do
            if (empty($data['TOTP'])) {
                return $this->returnToForm();
            } else {
                $member = Member::get()->byID(\Novatio\Helpers\Service\SiteHelper::Session()->get('TOTP.ID'));
                if (!$member) {
                    \Novatio\Helpers\Service\SiteHelper::Session()->clear('TOTP.ID');

                    return $this->returnToForm();
                }
                if ($member->validateTOTP($data['TOTP'])) {
                    \Novatio\Helpers\Service\SiteHelper::Session()->clear('TOTP.ID');

                    // $member->LogIn(\Novatio\Helpers\Service\SiteHelper::Session()->get('TOTP.Remember'));
                    $loginHandler->performLogin($member, $data, $this->getRequest());

                    // $data = [ 'Remember' => \Novatio\Helpers\Service\SiteHelper::Session()->get('TOTP.Remember') ];
                    // return $this->logInUserAndRedirect($data);
                    return $this->getRequestHandler()->redirect($redirect);
                } else {
                    $this->sessionMessage('Incorrect security token', 'bad');

                    return $this->returnToForm();
                }
            }
        } else {
            $member = call_user_func(
                [ $this->getAuthenticatorClass(), 'authenticate' ],
                $data,
                $this
            );
            if ($member) {
                if ($member->Has2FA) {
                    \Novatio\Helpers\Service\SiteHelper::Session()->set('TOTP.ID', $member->ID);
                    \Novatio\Helpers\Service\SiteHelper::Session()->set('TOTP.Remember', !empty($data['Remember']));
                } else {
                    // $member->LogIn(!empty($data['Remember']));
                    $loginHandler->performLogin($member, $data, $this->getRequest());

                    // return $this->logInUserAndRedirect($data);
                    return $this->getRequestHandler()->redirect($redirect);
                }
            } else {
                \Novatio\Helpers\Service\SiteHelper::Session()->set('SessionForms.MemberLoginForm.Email',
                    $data['Email']);
                \Novatio\Helpers\Service\SiteHelper::Session()->set('SessionForms.MemberLoginForm.Remember',
                    !empty($data['Remember']));
            }
            $this->returnToForm();
        }
    }

    protected function returnToForm()
    {
        if (isset($_REQUEST['BackURL'])) {
            $backURL = $_REQUEST['BackURL'];
        } else {
            $backURL = null;
        }

        if ($backURL) {
            \Novatio\Helpers\Service\SiteHelper::Session()->set('BackURL', $backURL);
        }

        // Show the right tab on failed login
        $loginLink = Director::absoluteURL(
            $this->controller->Link('login')
        );
        if ($backURL) {
            $loginLink .= '?BackURL=' . urlencode($backURL);
        }
        $loginLink .= '#' . $this->FormName() . '_tab';
        $this->controller->redirect($loginLink);
    }

    public function Actions()
    {
        $actions = parent::Actions();
        $fields = $this->Fields();

        // Remove the lost-password action from the TOTP token form and insert a cancel button
        if ($fields->fieldByName('TOTP')) {
            $actions->removeByName('forgotPassword');
            $actions->push(
                FormAction::create("cancel", _t('LeftAndMain.CANCEL', "Cancel"))
                    ->addExtraClass('btnw btn-outline-secondary')
                    ->setUseButtonTag(true)
            );
        }

        return $actions;
    }

    public function Fields()
    {
        if (!\Novatio\Helpers\Service\SiteHelper::Session()->get('TOTP.ID')) {
            return parent::Fields();
        }

        $fields = FieldList::create(
            TextField::create('TOTP', 'Security Token'),
            HiddenField::create('BackURL', null, \Novatio\Helpers\Service\SiteHelper::Session()->get('BackURL')),

        );

        if ($security_token = $this->getSecurityToken()) {
            $fields->push(HiddenField::create($security_token->getName(), null, $security_token->getSecurityID()));
        }

        foreach ($this->getExtraFields() as $field) {
            if (!$fields->fieldByName($field->getName())) {
                $fields->push($field);
            }
        }

        return $fields;
    }
}
