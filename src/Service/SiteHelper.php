<?php

namespace Novatio\Helpers\Service;

use SilverStripe\Control\Controller;

/**
 * Class SiteHelper
 * @author Terry Duivesteijn <terry@loungeroom.nl>
 * @author Gabrijel Gavranović <gabrijel@gavro.nl>
 */
class SiteHelper
{
    /**
     * @return \SilverStripe\Control\Session|void
     */
    public static function Session()
    {
        if (($controller = Controller::curr()) && ($request = $controller->getRequest())) {
            return $request->getSession();
        }
    }
}
