<?php

namespace GlpiPlugin\Medicaoeletronica;

use CommonDBTM;
use Html;
use Profile;
use Session;

class ProfileClass extends Profile
{
    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof Profile) {
            return __('Medição Eletrônica', 'medicaoeletronica');
        }
        return '';
    }

    public static function displayTabContentForItem(\CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Profile) {
            $profile_obj = new self();
            $profile_obj->showForm($item->getID());
        }
        return true;
    }

    public function showForm($ID, array $options = [])
    {
        if (!self::canView()) {
            return false;
        }

        echo "<div class='spaced'>";
        $profile = new Profile();
        $profile->getFromDB($ID);

        $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);

        if ($canedit) {
            echo "<form method='post' action='" . $profile->getFormURL() . "'>";
        }

        $rights = [
            [
                'itemtype' => 'GlpiPlugin\\Medicaoeletronica\\Config',
                'label'    => __('Medição Eletrônica', 'medicaoeletronica'),
                'field'    => 'medicaoeletronica',
            ],
        ];

        $matrix_options = [
            'title' => __('Medição Eletrônica', 'medicaoeletronica')
        ];

        $profile->displayRightsChoiceMatrix($rights, $matrix_options);

        if ($canedit) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $ID]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
            echo "</div>\n";
            Html::closeForm();
        }

        echo "</div>";

        return true;
    }
}
