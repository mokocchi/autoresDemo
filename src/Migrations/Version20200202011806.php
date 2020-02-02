<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200202011806 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE usuario_role (usuario_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_3E13F07ADB38439E (usuario_id), INDEX IDX_3E13F07AD60322AC (role_id), PRIMARY KEY(usuario_id, role_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE usuario_role ADD CONSTRAINT FK_3E13F07ADB38439E FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE usuario_role ADD CONSTRAINT FK_3E13F07AD60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE actividad ADD autor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE actividad ADD CONSTRAINT FK_8DF2BD0614D45BBE FOREIGN KEY (autor_id) REFERENCES usuario (id)');
        $this->addSql('CREATE INDEX IDX_8DF2BD0614D45BBE ON actividad (autor_id)');
        $this->addSql('ALTER TABLE tarea ADD autor_id INT DEFAULT NULL, ADD estado VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE tarea ADD CONSTRAINT FK_3CA0536614D45BBE FOREIGN KEY (autor_id) REFERENCES usuario (id)');
        $this->addSql('CREATE INDEX IDX_3CA0536614D45BBE ON tarea (autor_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE usuario_role DROP FOREIGN KEY FK_3E13F07AD60322AC');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE usuario_role');
        $this->addSql('ALTER TABLE actividad DROP FOREIGN KEY FK_8DF2BD0614D45BBE');
        $this->addSql('DROP INDEX IDX_8DF2BD0614D45BBE ON actividad');
        $this->addSql('ALTER TABLE actividad DROP autor_id');
        $this->addSql('ALTER TABLE tarea DROP FOREIGN KEY FK_3CA0536614D45BBE');
        $this->addSql('DROP INDEX IDX_3CA0536614D45BBE ON tarea');
        $this->addSql('ALTER TABLE tarea DROP autor_id, DROP estado');
    }
}
