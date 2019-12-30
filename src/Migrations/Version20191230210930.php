<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191230210930 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE salto (id INT AUTO_INCREMENT NOT NULL, planificacion_id INT NOT NULL, origen_id INT NOT NULL, respuesta VARCHAR(255) DEFAULT NULL, condicion VARCHAR(255) NOT NULL, INDEX IDX_2C590F1B4428E082 (planificacion_id), INDEX IDX_2C590F1B93529ECD (origen_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE salto_tarea (salto_id INT NOT NULL, tarea_id INT NOT NULL, INDEX IDX_DA8B25DFE31D7C12 (salto_id), INDEX IDX_DA8B25DF6D5BDFE1 (tarea_id), PRIMARY KEY(salto_id, tarea_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE planificacion (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE salto ADD CONSTRAINT FK_2C590F1B4428E082 FOREIGN KEY (planificacion_id) REFERENCES planificacion (id)');
        $this->addSql('ALTER TABLE salto ADD CONSTRAINT FK_2C590F1B93529ECD FOREIGN KEY (origen_id) REFERENCES tarea (id)');
        $this->addSql('ALTER TABLE salto_tarea ADD CONSTRAINT FK_DA8B25DFE31D7C12 FOREIGN KEY (salto_id) REFERENCES salto (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE salto_tarea ADD CONSTRAINT FK_DA8B25DF6D5BDFE1 FOREIGN KEY (tarea_id) REFERENCES tarea (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE actividad DROP FOREIGN KEY FK_8DF2BD064428E082');
        $this->addSql('ALTER TABLE actividad ADD planificacion_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE actividad ADD CONSTRAINT FK_8DF2BD064428E082 FOREIGN KEY (planificacion_id) REFERENCES planificacion (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8DF2BD064428E082 ON actividad (planificacion_id)');
        $this->addSql('ALTER TABLE actividad RENAME INDEX idx_8df2bd064428e082 TO IDX_8DF2BD06E1F40F99');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE salto_tarea DROP FOREIGN KEY FK_DA8B25DFE31D7C12');
        $this->addSql('ALTER TABLE salto DROP FOREIGN KEY FK_2C590F1B4428E082');
        $this->addSql('ALTER TABLE actividad DROP FOREIGN KEY FK_8DF2BD064428E082');
        $this->addSql('DROP TABLE salto');
        $this->addSql('DROP TABLE salto_tarea');
        $this->addSql('DROP TABLE planificacion');
        $this->addSql('DROP INDEX UNIQ_8DF2BD064428E082 ON actividad');
        $this->addSql('ALTER TABLE actividad DROP planificacion_id');
        $this->addSql('ALTER TABLE actividad RENAME INDEX idx_8df2bd06e1f40f99 TO IDX_8DF2BD064428E082');
    }
}
