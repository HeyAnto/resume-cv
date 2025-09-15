<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250915124405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE education ADD resume_section_id INT NOT NULL');
        $this->addSql('ALTER TABLE education ADD CONSTRAINT FK_DB0A5ED26A5391B3 FOREIGN KEY (resume_section_id) REFERENCES resume_section (id)');
        $this->addSql('CREATE INDEX IDX_DB0A5ED26A5391B3 ON education (resume_section_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE education DROP FOREIGN KEY FK_DB0A5ED26A5391B3');
        $this->addSql('DROP INDEX IDX_DB0A5ED26A5391B3 ON education');
        $this->addSql('ALTER TABLE education DROP resume_section_id');
    }
}
