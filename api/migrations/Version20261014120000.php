<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261014120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modèle concours Thé Tip Top (users, lots, tickets, claims, audit, outbox)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_user (
            id SERIAL NOT NULL,
            email VARCHAR(180) NOT NULL,
            password_hash VARCHAR(255) DEFAULT NULL,
            oauth_provider VARCHAR(20) DEFAULT NULL,
            oauth_subject VARCHAR(255) DEFAULT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            birthdate DATE DEFAULT NULL,
            gender VARCHAR(30) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            marketing_opt_in BOOLEAN DEFAULT false NOT NULL,
            roles JSON NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_app_user_email ON app_user (email)');
        $this->addSql('CREATE UNIQUE INDEX uniq_app_user_oauth ON app_user (oauth_provider, oauth_subject)');

        $this->addSql('CREATE TABLE prize_type (
            id SERIAL NOT NULL,
            code VARCHAR(50) NOT NULL,
            label VARCHAR(150) NOT NULL,
            percent INT NOT NULL,
            stock_target INT NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_prize_type_code ON prize_type (code)');

        $this->addSql('CREATE TABLE contest_state (
            id SERIAL NOT NULL,
            status VARCHAR(20) NOT NULL,
            open_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            claim_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            updated_by INT DEFAULT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('ALTER TABLE contest_state ADD CONSTRAINT FK_CONTEST_STATE_UPDATED_BY FOREIGN KEY (updated_by) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE ticket_code (
            id SERIAL NOT NULL,
            code VARCHAR(10) NOT NULL,
            prize_type_id INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            used_by_user_id INT DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_ticket_code_code ON ticket_code (code)');
        $this->addSql('CREATE INDEX idx_ticket_code_status ON ticket_code (status)');
        $this->addSql('CREATE INDEX idx_ticket_code_prize_type ON ticket_code (prize_type_id)');
        $this->addSql('ALTER TABLE ticket_code ADD CONSTRAINT FK_TICKET_PRIZE_TYPE FOREIGN KEY (prize_type_id) REFERENCES prize_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE ticket_code ADD CONSTRAINT FK_TICKET_USED_BY FOREIGN KEY (used_by_user_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE participation (
            id SERIAL NOT NULL,
            user_id INT NOT NULL,
            ticket_code_id INT NOT NULL,
            ip VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_participation_ticket ON participation (ticket_code_id)');
        $this->addSql('CREATE INDEX idx_participation_user ON participation (user_id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_PARTICIPATION_USER FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_PARTICIPATION_TICKET FOREIGN KEY (ticket_code_id) REFERENCES ticket_code (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE grand_prize_draw (
            id SERIAL NOT NULL,
            winner_user_id INT NOT NULL,
            drawn_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            drawn_by_user_id INT NOT NULL,
            seed_hash VARCHAR(128) NOT NULL,
            audit_payload JSON NOT NULL,
            eligible_count INT NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('ALTER TABLE grand_prize_draw ADD CONSTRAINT FK_DRAW_WINNER FOREIGN KEY (winner_user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE grand_prize_draw ADD CONSTRAINT FK_DRAW_BY FOREIGN KEY (drawn_by_user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE claim (
            id SERIAL NOT NULL,
            participation_id INT DEFAULT NULL,
            grand_prize_draw_id INT DEFAULT NULL,
            channel VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,
            delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            delivered_by_user_id INT DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_claim_participation ON claim (participation_id)');
        $this->addSql('CREATE INDEX idx_claim_status ON claim (status)');
        $this->addSql('ALTER TABLE claim ADD CONSTRAINT FK_CLAIM_PARTICIPATION FOREIGN KEY (participation_id) REFERENCES participation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE claim ADD CONSTRAINT FK_CLAIM_DRAW FOREIGN KEY (grand_prize_draw_id) REFERENCES grand_prize_draw (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE claim ADD CONSTRAINT FK_CLAIM_DELIVERED_BY FOREIGN KEY (delivered_by_user_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE email_outbox (
            id SERIAL NOT NULL,
            to_email VARCHAR(180) NOT NULL,
            template VARCHAR(80) NOT NULL,
            payload_json JSON NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            sent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_email_outbox_status ON email_outbox (status)');

        $this->addSql('CREATE TABLE audit_log (
            id SERIAL NOT NULL,
            actor_id INT DEFAULT NULL,
            action VARCHAR(80) NOT NULL,
            entity VARCHAR(80) NOT NULL,
            entity_id VARCHAR(64) DEFAULT NULL,
            payload_json JSON NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_audit_log_action ON audit_log (action)');
        $this->addSql('CREATE INDEX idx_audit_log_created_at ON audit_log (created_at)');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_AUDIT_ACTOR FOREIGN KEY (actor_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log DROP CONSTRAINT FK_AUDIT_ACTOR');
        $this->addSql('ALTER TABLE claim DROP CONSTRAINT FK_CLAIM_DELIVERED_BY');
        $this->addSql('ALTER TABLE claim DROP CONSTRAINT FK_CLAIM_DRAW');
        $this->addSql('ALTER TABLE claim DROP CONSTRAINT FK_CLAIM_PARTICIPATION');
        $this->addSql('ALTER TABLE grand_prize_draw DROP CONSTRAINT FK_DRAW_BY');
        $this->addSql('ALTER TABLE grand_prize_draw DROP CONSTRAINT FK_DRAW_WINNER');
        $this->addSql('ALTER TABLE participation DROP CONSTRAINT FK_PARTICIPATION_TICKET');
        $this->addSql('ALTER TABLE participation DROP CONSTRAINT FK_PARTICIPATION_USER');
        $this->addSql('ALTER TABLE ticket_code DROP CONSTRAINT FK_TICKET_USED_BY');
        $this->addSql('ALTER TABLE ticket_code DROP CONSTRAINT FK_TICKET_PRIZE_TYPE');
        $this->addSql('ALTER TABLE contest_state DROP CONSTRAINT FK_CONTEST_STATE_UPDATED_BY');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE email_outbox');
        $this->addSql('DROP TABLE claim');
        $this->addSql('DROP TABLE grand_prize_draw');
        $this->addSql('DROP TABLE participation');
        $this->addSql('DROP TABLE ticket_code');
        $this->addSql('DROP TABLE contest_state');
        $this->addSql('DROP TABLE prize_type');
        $this->addSql('DROP TABLE app_user');
    }
}
