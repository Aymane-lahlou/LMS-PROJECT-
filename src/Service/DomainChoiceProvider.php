<?php

namespace App\Service;

class DomainChoiceProvider
{
    /**
     * @return array<string,string>
     */
    public function getSpecialtyChoices(): array
    {
        return [
            'Informatique' => 'informatique',
            'Génie logiciel' => 'genie_logiciel',
            'Réseaux et Télécommunications' => 'reseaux',
            'Data Science' => 'data',
            'Cybersécurité' => 'cybersecurite',
        ];
    }

    /**
     * @return array<string,int>
     */
    public function getStudyYearChoices(): array
    {
        return [
            '1ère année' => 1,
            '2ème année' => 2,
            '3ème année' => 3,
            '4ème année' => 4,
            '5ème année' => 5,
        ];
    }
}
