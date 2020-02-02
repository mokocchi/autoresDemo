<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200202105757 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE actividad ADD estado_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE actividad ADD CONSTRAINT FK_8DF2BD069F5A440B FOREIGN KEY (estado_id) REFERENCES estado (id)');
        $this->addSql('CREATE INDEX IDX_8DF2BD069F5A440B ON actividad (estado_id)');
        $this->addSql('ALTER TABLE tarea ADD estado_id INT DEFAULT NULL, DROP estado');
        $this->addSql('ALTER TABLE tarea ADD CONSTRAINT FK_3CA053669F5A440B FOREIGN KEY (estado_id) REFERENCES estado (id)');
        $this->addSql('CREATE INDEX IDX_3CA053669F5A440B ON tarea (estado_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE actividad DROP FOREIGN KEY FK_8DF2BD069F5A440B');
        $this->addSql('DROP INDEX IDX_8DF2BD069F5A440B ON actividad');
        $this->addSql('ALTER TABLE actividad DROP estado_id');
        $this->addSql('ALTER TABLE tarea DROP FOREIGN KEY FK_3CA053669F5A440B');
        $this->addSql('DROP INDEX IDX_3CA053669F5A440B ON tarea');
        $this->addSql('ALTER TABLE tarea ADD estado VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP estado_id');
    }
}
