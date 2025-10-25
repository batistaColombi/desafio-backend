<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024200107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE church_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE member_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE member_transfer_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE church (id INT NOT NULL, name VARCHAR(255) NOT NULL, document_type VARCHAR(20) NOT NULL, document_number VARCHAR(50) NOT NULL, internal_code VARCHAR(50) NOT NULL, phone VARCHAR(20) DEFAULT NULL, address_street VARCHAR(255) DEFAULT NULL, address_number VARCHAR(10) DEFAULT NULL, address_complement VARCHAR(100) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, state VARCHAR(50) DEFAULT NULL, cep VARCHAR(20) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, members_limit INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE member (id INT NOT NULL, church_id INT NOT NULL, name VARCHAR(150) NOT NULL, document_type VARCHAR(20) NOT NULL, document_number VARCHAR(20) NOT NULL, birth_date DATE DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, address_street VARCHAR(255) DEFAULT NULL, address_number VARCHAR(10) DEFAULT NULL, address_complement VARCHAR(100) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, state VARCHAR(2) DEFAULT NULL, cep VARCHAR(9) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_70E4FA78C1538FD4 ON member (church_id)');
        $this->addSql('CREATE TABLE member_transfer (id INT NOT NULL, member_id INT NOT NULL, from_church_id INT NOT NULL, to_church_id INT NOT NULL, transfer_date DATE NOT NULL, created_by VARCHAR(180) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B7E9002A7597D3FE ON member_transfer (member_id)');
        $this->addSql('CREATE INDEX IDX_B7E9002AD1CF1A6E ON member_transfer (from_church_id)');
        $this->addSql('CREATE INDEX IDX_B7E9002AF0F058A5 ON member_transfer (to_church_id)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE member ADD CONSTRAINT FK_70E4FA78C1538FD4 FOREIGN KEY (church_id) REFERENCES church (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE member_transfer ADD CONSTRAINT FK_B7E9002A7597D3FE FOREIGN KEY (member_id) REFERENCES member (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE member_transfer ADD CONSTRAINT FK_B7E9002AD1CF1A6E FOREIGN KEY (from_church_id) REFERENCES church (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE member_transfer ADD CONSTRAINT FK_B7E9002AF0F058A5 FOREIGN KEY (to_church_id) REFERENCES church (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE church_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE member_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE member_transfer_id_seq CASCADE');
        $this->addSql('ALTER TABLE member DROP CONSTRAINT FK_70E4FA78C1538FD4');
        $this->addSql('ALTER TABLE member_transfer DROP CONSTRAINT FK_B7E9002A7597D3FE');
        $this->addSql('ALTER TABLE member_transfer DROP CONSTRAINT FK_B7E9002AD1CF1A6E');
        $this->addSql('ALTER TABLE member_transfer DROP CONSTRAINT FK_B7E9002AF0F058A5');
        $this->addSql('DROP TABLE church');
        $this->addSql('DROP TABLE member');
        $this->addSql('DROP TABLE member_transfer');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
