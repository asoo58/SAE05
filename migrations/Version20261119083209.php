<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261119083209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tournee ADD vehicule_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tournee ADD saison_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tournee ADD CONSTRAINT FK_EBF67D7E4A4A3511 FOREIGN KEY (vehicule_id) REFERENCES vehicule (id)');
        $this->addSql('ALTER TABLE tournee ADD CONSTRAINT FK_EBF67D7EF965414C FOREIGN KEY (saison_id) REFERENCES saison (id)');
        $this->addSql('CREATE INDEX IDX_EBF67D7E4A4A3511 ON tournee (vehicule_id)');
        $this->addSql('CREATE INDEX IDX_EBF67D7EF965414C ON tournee (saison_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA legacy');
        $this->addSql('ALTER TABLE tournee DROP CONSTRAINT FK_EBF67D7E4A4A3511');
        $this->addSql('ALTER TABLE tournee DROP CONSTRAINT FK_EBF67D7EF965414C');
        $this->addSql('DROP INDEX IDX_EBF67D7E4A4A3511');
        $this->addSql('DROP INDEX IDX_EBF67D7EF965414C');
        $this->addSql('ALTER TABLE tournee DROP vehicule_id');
        $this->addSql('ALTER TABLE tournee DROP saison_id');
    }
}
