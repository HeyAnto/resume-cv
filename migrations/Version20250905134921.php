<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250905134921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE certification (id INT AUTO_INCREMENT NOT NULL, resume_section_id INT NOT NULL, title VARCHAR(98) NOT NULL, authority VARCHAR(98) NOT NULL, authority_link VARCHAR(98) DEFAULT NULL, issued_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', expiration_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', credential_id VARCHAR(255) NOT NULL, credential_url VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6C3C6D756A5391B3 (resume_section_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, company_name VARCHAR(98) NOT NULL, description LONGTEXT DEFAULT NULL, location VARCHAR(32) NOT NULL, profile_picture_path VARCHAR(255) DEFAULT NULL, website_name VARCHAR(98) DEFAULT NULL, website_link VARCHAR(98) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4FBF094FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE company_default_tag (company_id INT NOT NULL, default_tag_id INT NOT NULL, INDEX IDX_343F7C36979B1AD6 (company_id), INDEX IDX_343F7C365D836842 (default_tag_id), PRIMARY KEY(company_id, default_tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE company_tag (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, label VARCHAR(48) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_77A33EB979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE default_tag (id INT AUTO_INCREMENT NOT NULL, icon_path VARCHAR(255) NOT NULL, label VARCHAR(48) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE education (id INT AUTO_INCREMENT NOT NULL, school_name VARCHAR(98) NOT NULL, school_link VARCHAR(98) DEFAULT NULL, degree VARCHAR(98) NOT NULL, location VARCHAR(32) NOT NULL, description VARCHAR(160) DEFAULT NULL, start_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', end_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE experience (id INT AUTO_INCREMENT NOT NULL, resume_section_id INT NOT NULL, company_name VARCHAR(98) NOT NULL, company_link VARCHAR(98) DEFAULT NULL, job VARCHAR(32) NOT NULL, location VARCHAR(32) NOT NULL, description VARCHAR(160) DEFAULT NULL, start_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', end_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_590C1036A5391B3 (resume_section_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_offer (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, title VARCHAR(98) NOT NULL, description LONGTEXT NOT NULL, email_link VARCHAR(98) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_288A3A4E979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, description VARCHAR(280) NOT NULL, image_path VARCHAR(255) DEFAULT NULL, is_visible TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5A8A6C8DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE profile (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, profile_picture_path VARCHAR(255) DEFAULT NULL, display_name VARCHAR(48) NOT NULL, pronouns VARCHAR(12) DEFAULT NULL, job VARCHAR(32) NOT NULL, location VARCHAR(32) DEFAULT NULL, description VARCHAR(160) DEFAULT NULL, website_name VARCHAR(98) DEFAULT NULL, website_link VARCHAR(98) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE profile_follows (profile_source INT NOT NULL, profile_target INT NOT NULL, INDEX IDX_98DD54EF37A01814 (profile_source), INDEX IDX_98DD54EF2E45489B (profile_target), PRIMARY KEY(profile_source, profile_target)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE profile_post_likes (profile_id INT NOT NULL, post_id INT NOT NULL, INDEX IDX_19BAF5CFCCFA12B8 (profile_id), INDEX IDX_19BAF5CF4B89032C (post_id), PRIMARY KEY(profile_id, post_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, resume_section_id INT NOT NULL, project_name VARCHAR(98) NOT NULL, project_link VARCHAR(98) NOT NULL, description VARCHAR(160) NOT NULL, image_path VARCHAR(255) DEFAULT NULL, image_path2 VARCHAR(255) DEFAULT NULL, image_path3 VARCHAR(255) DEFAULT NULL, date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2FB3D0EE6A5391B3 (resume_section_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE resume_section (id INT AUTO_INCREMENT NOT NULL, profile_id INT NOT NULL, label VARCHAR(255) NOT NULL, order_index INT NOT NULL, is_visible TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1B33C507CCFA12B8 (profile_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, profile_id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649CCFA12B8 (profile_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE volunteering (id INT AUTO_INCREMENT NOT NULL, resume_section_id INT NOT NULL, organization_name VARCHAR(98) NOT NULL, organization_link VARCHAR(98) DEFAULT NULL, role VARCHAR(32) NOT NULL, description VARCHAR(160) DEFAULT NULL, location VARCHAR(32) NOT NULL, start_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', end_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7854E8EE6A5391B3 (resume_section_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D756A5391B3 FOREIGN KEY (resume_section_id) REFERENCES resume_section (id)');
        $this->addSql('ALTER TABLE company ADD CONSTRAINT FK_4FBF094FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE company_default_tag ADD CONSTRAINT FK_343F7C36979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE company_default_tag ADD CONSTRAINT FK_343F7C365D836842 FOREIGN KEY (default_tag_id) REFERENCES default_tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE company_tag ADD CONSTRAINT FK_77A33EB979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE experience ADD CONSTRAINT FK_590C1036A5391B3 FOREIGN KEY (resume_section_id) REFERENCES resume_section (id)');
        $this->addSql('ALTER TABLE job_offer ADD CONSTRAINT FK_288A3A4E979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE profile_follows ADD CONSTRAINT FK_98DD54EF37A01814 FOREIGN KEY (profile_source) REFERENCES profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE profile_follows ADD CONSTRAINT FK_98DD54EF2E45489B FOREIGN KEY (profile_target) REFERENCES profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE profile_post_likes ADD CONSTRAINT FK_19BAF5CFCCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE profile_post_likes ADD CONSTRAINT FK_19BAF5CF4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE6A5391B3 FOREIGN KEY (resume_section_id) REFERENCES resume_section (id)');
        $this->addSql('ALTER TABLE resume_section ADD CONSTRAINT FK_1B33C507CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649CCFA12B8 FOREIGN KEY (profile_id) REFERENCES profile (id)');
        $this->addSql('ALTER TABLE volunteering ADD CONSTRAINT FK_7854E8EE6A5391B3 FOREIGN KEY (resume_section_id) REFERENCES resume_section (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D756A5391B3');
        $this->addSql('ALTER TABLE company DROP FOREIGN KEY FK_4FBF094FA76ED395');
        $this->addSql('ALTER TABLE company_default_tag DROP FOREIGN KEY FK_343F7C36979B1AD6');
        $this->addSql('ALTER TABLE company_default_tag DROP FOREIGN KEY FK_343F7C365D836842');
        $this->addSql('ALTER TABLE company_tag DROP FOREIGN KEY FK_77A33EB979B1AD6');
        $this->addSql('ALTER TABLE experience DROP FOREIGN KEY FK_590C1036A5391B3');
        $this->addSql('ALTER TABLE job_offer DROP FOREIGN KEY FK_288A3A4E979B1AD6');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DA76ED395');
        $this->addSql('ALTER TABLE profile_follows DROP FOREIGN KEY FK_98DD54EF37A01814');
        $this->addSql('ALTER TABLE profile_follows DROP FOREIGN KEY FK_98DD54EF2E45489B');
        $this->addSql('ALTER TABLE profile_post_likes DROP FOREIGN KEY FK_19BAF5CFCCFA12B8');
        $this->addSql('ALTER TABLE profile_post_likes DROP FOREIGN KEY FK_19BAF5CF4B89032C');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE6A5391B3');
        $this->addSql('ALTER TABLE resume_section DROP FOREIGN KEY FK_1B33C507CCFA12B8');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649CCFA12B8');
        $this->addSql('ALTER TABLE volunteering DROP FOREIGN KEY FK_7854E8EE6A5391B3');
        $this->addSql('DROP TABLE certification');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE company_default_tag');
        $this->addSql('DROP TABLE company_tag');
        $this->addSql('DROP TABLE default_tag');
        $this->addSql('DROP TABLE education');
        $this->addSql('DROP TABLE experience');
        $this->addSql('DROP TABLE job_offer');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE profile');
        $this->addSql('DROP TABLE profile_follows');
        $this->addSql('DROP TABLE profile_post_likes');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE resume_section');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE volunteering');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
