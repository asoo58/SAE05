<?php

namespace App\Service\Exception;

/**
 * Un autre utilisateur a modifié les paniers disponibles entre le calcul
 * du plan et la création des tournées.
 */
final class OrganisationConcurrenteException extends \RuntimeException
{
}
