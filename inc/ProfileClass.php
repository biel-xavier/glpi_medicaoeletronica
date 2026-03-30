<?php

namespace GlpiPlugin\Medicaoeletronica;

use CommonDBTM;
use Html;
use Profile;
use Session;

class ProfileClass extends Profile
{
    public static $rightname = 'profile';

    public static function getAllRights(): array
    {
        return [
            [
                'itemtype' => 'GlpiPlugin\\Medicaoeletronica\\Config',
                'label'    => __('Medição Eletrônica', 'medicaoeletronica'),
                'field'    => 'medicaoeletronica',
            ],
            [
                'itemtype' => 'GlpiPlugin\\Medicaoeletronica\\History',
                'label'    => __('Histórico da API', 'medicaoeletronica'),
                'field'    => 'medicaoeletronica_history',
                'rights'   => [READ => __('Read')],
            ],
        ];
    }

    public static function addDefaultProfileInfos(int $profiles_id, array $rights): void
    {
        $profileRight = new \ProfileRight();

        foreach ($rights as $right => $value) {
            if (!countElementsInTable('glpi_profilerights', [
                'profiles_id' => $profiles_id,
                'name'        => $right,
            ])) {
                $profileRight->add([
                    'profiles_id' => $profiles_id,
                    'name'        => $right,
                    'rights'      => $value,
                ]);

                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }

    public static function createFirstAccess(int $profiles_id): void
    {
        self::addDefaultProfileInfos($profiles_id, [
            'medicaoeletronica'         => ALLSTANDARDRIGHT,
            'medicaoeletronica_history' => READ,
        ]);
    }

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
            self::addDefaultProfileInfos($item->getID(), [
                'medicaoeletronica'         => 0,
                'medicaoeletronica_history' => 0,
            ]);

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

        $matrix_options = [
            'title'   => __('Medição Eletrônica', 'medicaoeletronica'),
            'canedit' => $canedit,
        ];

        $profile->displayRightsChoiceMatrix(self::getAllRights(), $matrix_options);

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
