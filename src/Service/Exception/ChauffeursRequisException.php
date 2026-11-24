<?php

namespace App\Service\Exception;

/**
 * Levée lorsqu'il manque un chauffeur pour une ou plusieurs camionnettes du plan.
 */
final class ChauffeursRequisException extends \DomainException
{
    /** @var list<int> */
    private array $vehiculesAAffecter = [];

    /**
     * @param list<int> $vehiculesAAffecter
     */
    public function setVehiculesAAffecter(array $vehiculesAAffecter): static
    {
        $this->vehiculesAAffecter = $vehiculesAAffecter;

        return $this;
    }

    /**
     * @return list<int>
     */
    public function getVehiculesAAffecter(): array
    {
        return $this->vehiculesAAffecter;
    }
}
