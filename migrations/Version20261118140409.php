<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261118140409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE adherent ADD nom VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE adherent ADD prenom VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE recette ALTER tsv_recherche TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE recette ALTER embedding TYPE VARCHAR(255)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE adherent DROP nom');
        $this->addSql('ALTER TABLE adherent DROP prenom');
        $this->addSql('ALTER TABLE recette ALTER tsv_recherche TYPE TEXT');
        $this->addSql('ALTER TABLE recette ALTER embedding TYPE VARCHAR');
    }
}
