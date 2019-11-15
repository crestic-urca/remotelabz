<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191115141912 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activity ADD scope VARCHAR(255) NOT NULL, DROP used_alone, DROP used_in_group, DROP used_together_in_course');
        $this->addSql('ALTER TABLE lab_instance ADD scope VARCHAR(255) NOT NULL, DROP is_used_alone, DROP is_used_in_group, DROP is_used_together_in_course');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE activity ADD used_alone TINYINT(1) NOT NULL, ADD used_in_group TINYINT(1) NOT NULL, ADD used_together_in_course TINYINT(1) NOT NULL, DROP scope');
        $this->addSql('ALTER TABLE lab_instance ADD is_used_alone TINYINT(1) NOT NULL, ADD is_used_in_group TINYINT(1) NOT NULL, ADD is_used_together_in_course TINYINT(1) NOT NULL, DROP scope');
    }
}
