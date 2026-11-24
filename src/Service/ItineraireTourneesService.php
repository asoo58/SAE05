<?php

namespace App\Service;

use App\Entity\Depot;
use App\Entity\EtapeTournee;
use App\Entity\Tournee;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Réorganisation des itinéraires de tournée.
 *
 * Le point de dépôt est identifié par la clé composite (tournée, dépôt) :
 * transférer un dépôt revient donc à supprimer son étape puis à en créer une
 * nouvelle dans la tournée cible.
 */
final class ItineraireTourneesService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return EtapeTournee[]
     */
    public function etapesOrdonnees(Tournee $tournee): array
    {
        return $this->entityManager->getRepository(EtapeTournee::class)->findBy(
            ['tournee' => $tournee],
            ['ordrePassage' => 'ASC']
        );
    }

    /**
     * Déplace une étape d'un cran vers le haut ou vers le bas.
     *
     * @param string $direction "haut" ou "bas"
     */
    public function deplacer(Tournee $tournee, Depot $depot, string $direction): bool
    {
        $etapes = $this->etapesOrdonnees($tournee);
        $index = $this->indexDuDepot($etapes, $depot);

        if ($index === null) {
            return false;
        }

        $cible = $direction === 'haut' ? $index - 1 : $index + 1;
        if ($cible < 0 || $cible >= count($etapes)) {
            return false;
        }

        $element = $etapes[$index];
        $etapes[$index] = $etapes[$cible];
        $etapes[$cible] = $element;

        $this->appliquerOrdre($etapes);

        return true;
    }

    /**
     * Change un dépôt de tournée et le place en fin d'itinéraire.
     */
    public function transferer(Tournee $source, Depot $depot, Tournee $cible): bool
    {
        if ($source->getId() === $cible->getId()) {
            return false;
        }

        $etape = $this->entityManager->getRepository(EtapeTournee::class)->findOneBy([
            'tournee' => $source,
            'depot' => $depot,
        ]);

        if ($etape === null) {
            return false;
        }

        $this->entityManager->remove($etape);
        $this->entityManager->flush();

        $nouvelle = (new EtapeTournee())
            ->setTournee($cible)
            ->setDepot($depot)
            ->setOrdrePassage(count($this->etapesOrdonnees($cible)) + 1);

        $this->entityManager->persist($nouvelle);
        $this->entityManager->flush();

        $this->renumeroter($source);

        return true;
    }

    /**
     * Ajoute un dépôt en fin d'itinéraire.
     *
     * Le dépôt peut déjà appartenir à une autre tournée : la clé composite
     * (tournée, dépôt) autorise cette double appartenance.
     */
    public function ajouter(Tournee $tournee, Depot $depot): bool
    {
        $existe = $this->entityManager->getRepository(EtapeTournee::class)->findOneBy([
            'tournee' => $tournee,
            'depot' => $depot,
        ]);

        if ($existe !== null) {
            return false;
        }

        $etape = (new EtapeTournee())
            ->setTournee($tournee)
            ->setDepot($depot)
            ->setOrdrePassage(count($this->etapesOrdonnees($tournee)) + 1);

        $this->entityManager->persist($etape);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Retire un dépôt d'une tournée puis recolle les numéros d'ordre.
     */
    public function retirer(Tournee $tournee, Depot $depot): bool
    {
        $etape = $this->entityManager->getRepository(EtapeTournee::class)->findOneBy([
            'tournee' => $tournee,
            'depot' => $depot,
        ]);

        if ($etape === null) {
            return false;
        }

        $this->entityManager->remove($etape);
        $this->entityManager->flush();

        $this->renumeroter($tournee);

        return true;
    }

    /**
     * Recolle les numéros d'ordre sur 1..n.
     */
    public function renumeroter(Tournee $tournee): void
    {
        $this->appliquerOrdre($this->etapesOrdonnees($tournee));
    }

    /**
     * @param EtapeTournee[] $etapes
     */
    private function appliquerOrdre(array $etapes): void
    {
        foreach (array_values($etapes) as $position => $etape) {
            $etape->setOrdrePassage($position + 1);
        }

        $this->entityManager->flush();
    }

    /**
     * @param EtapeTournee[] $etapes
     */
    private function indexDuDepot(array $etapes, Depot $depot): ?int
    {
        foreach ($etapes as $index => $etape) {
            if ($etape->getDepot()?->getId() === $depot->getId()) {
                return $index;
            }
        }

        return null;
    }
}
