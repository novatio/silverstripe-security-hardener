<?php

namespace SecurityHardener\Extensions;

class SiteConfigExtension extends \DataExtension
{
    /**
     * @var array
     */
    private static $db = [
        'EnableLoginLockout'          => 'Boolean',
        'EnableTwoFactorAuth'         => 'Boolean',
        'LockOutAfterIncorrectLogins' => 'Int',
        'LockOutDelayMins'            => 'Int',
        'LockoutNotificationEmail'    => 'Varchar(254)',
    ];

    /**
     * @var array
     */
    private static $defaults = [
        'EnableLoginLockout'          => false,
        'LockOutAfterIncorrectLogins' => 5,
        'LockOutDelayMins'            => 60,
    ];

    /**
     * For some reason we cannot override the previously overwritten class for CMSProfileController by updating
     * Injector params. CMSProfileController kept referring to the 2fa one...
     *
     * Write config to YML and flush cache after saving.
     */
    public function onAfterWrite()
    {
        $this->writeConfig();
    }

    /**
     * @param \FieldList $fields
     */
    public function updateCMSFields(\FieldList $fields)
    {
        $fields->findOrMakeTab('Root.Security', _t('SecurityAdmin.MENUTITLE', 'Security'));

        $fields->addFieldsToTab('Root.Security', [
            \CheckboxField::create('EnableLoginLockout', $this->owner->fieldLabel('EnableLoginLockout')),
            \DisplayLogicWrapper::create(
                \TextField::create('LockOutAfterIncorrectLogins', $this->owner->fieldLabel('LockOutAfterIncorrectLogins')),
                \TextField::create('LockOutDelayMins', $this->owner->fieldLabel('LockOutDelayMins')),
                \EmailField::create('LockoutNotificationEmail', $this->owner->fieldLabel('LockoutNotificationEmail'))
            )->displayIf('EnableLoginLockout')->isChecked()->end(),
            \CheckboxField::create('EnableTwoFactorAuth', $this->owner->fieldLabel('EnableTwoFactorAuth')),
        ]);

        return $fields;
    }

    /**
     * Writes the config to a YAML config file.
     */
    protected function writeConfig()
    {
        $config = [];

        $this->enableLoginLockoutIfEnabled($config);
        $this->disableTwoFactorAuthIfNotEnabled($config);

        // add yaml header (important for after twofactorauth)
        $yaml = "---\r\nName: security-hardener\r\nAfter: 'twofactorauth/*'\r\n---\r\n"
            // add the parse config text
            . \Symfony\Component\Yaml\Yaml::dump($config, $inline = 9, $indent = 2, $flags = 0);

        // save to file
        file_put_contents(BASE_PATH . '/security-hardener/_config/security-hardener-config.yml', $yaml);

        // flush cache
        foreach(\ClassInfo::implementorsOf('Flushable') as $class) {
            $class::flush();
        }
    }

    /**
     * @param $config
     */
    protected function enableLoginLockoutIfEnabled(&$config)
    {
        if ($this->owner->EnableLoginLockout &&
            $this->owner->LockOutAfterIncorrectLogins &&
            $this->owner->LockOutDelayMins
        ) {
            $config['Member'] = [
                'lock_out_after_incorrect_logins' => (int)$this->owner->LockOutAfterIncorrectLogins,
                'lock_out_delay_mins'             => (int)$this->owner->LockOutDelayMins,
            ];
        }
    }

    /**
     * @param $config
     */
    protected function disableTwoFactorAuthIfNotEnabled(&$config)
    {
        if (!$this->owner->EnableTwoFactorAuth) {
            $config['CMSSecurity'] = [
                'reauth_enabled' => true,
            ];

            $config['Injector'] = [
                'MemberLoginForm' => [
                    'class' => class_exists('\AdminLoginForm') ? 'AdminLoginForm' : 'MemberLoginForm'
                ],
                'ChangePasswordForm' => [
                    'class' => 'ChangePasswordForm'
                ],
                'CMSMemberLoginForm' => [
                    'class' => 'CMSMemberLoginForm'
                ],
                'CMSProfileController' => [
                    'class' => 'CMSProfileController'
                ],
            ];

            \Member::remove_extension('_2fa\Extensions\Member');
        } else {
            $config['_2fa\Extensions\Member'] = [
                'validated_activation_mode' => true,
                'admins_can_disable'        => true,
            ];
        }
    }

}