<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200202105723 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE actividad DROP estado_id');
        $this->addSql('ALTER TABLE tarea ADD autor_id INT DEFAULT NULL, ADD estado VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE tarea ADD CONSTRAINT FK_3CA0536614D45BBE FOREIGN KEY (autor_id) REFERENCES usuario (id)');
        $this->addSql('CREATE INDEX IDX_3CA0536614D45BBE ON tarea (autor_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE actividad ADD estado_id INT NOT NULL');
        $this->addSql('ALTER TABLE tarea DROP FOREIGN KEY FK_3CA0536614D45BBE');
        $this->addSql('DROP INDEX IDX_3CA0536614D45BBE ON tarea');
        $this->addSql('ALTER TABLE tarea DROP autor_id, DROP estado');
    }
}
