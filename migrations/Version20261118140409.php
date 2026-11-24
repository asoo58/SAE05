<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118140409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout nom et prenom à adherent (et correction des types PostgreSQL)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS btree_gin;');
        
        $this->addSql('ALTER TABLE adherent ADD nom VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE adherent ADD prenom VARCHAR(100) NOT NULL');
        
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE adherent DROP nom');
        $this->addSql('ALTER TABLE adherent DROP prenom');
        
    }
}