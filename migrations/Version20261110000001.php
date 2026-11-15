<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create complete restaurant domain schema (users, suppliers, ingredients, preparations, dishes, menus, orders, stock movements)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE customer_order (id INT AUTO_INCREMENT NOT NULL, table_number INT NOT NULL, status VARCHAR(30) NOT NULL, waiter_name VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE menu (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, price DOUBLE PRECISION NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE menu_dish (menu_id INT NOT NULL, dish_id INT NOT NULL, INDEX IDX_5D327CF6CCD7E912 (menu_id), INDEX IDX_5D327CF6148EB0CB (dish_id), PRIMARY KEY (menu_id, dish_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dish_ingredient (id INT AUTO_INCREMENT NOT NULL, quantity DOUBLE PRECISION NOT NULL, unit VARCHAR(20) NOT NULL, dish_id INT NOT NULL, ingredient_id INT NOT NULL, INDEX IDX_77196056148EB0CB (dish_id), INDEX IDX_77196056933FE08C (ingredient_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ingredient (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, unit VARCHAR(20) NOT NULL, current_stock DOUBLE PRECISION NOT NULL, min_stock DOUBLE PRECISION NOT NULL, unit_cost DOUBLE PRECISION NOT NULL, barcode VARCHAR(60) DEFAULT NULL, allergens JSON NOT NULL, supplier_id INT DEFAULT NULL, INDEX IDX_6BAF78702ADD6D8C (supplier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dish (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, category VARCHAR(30) NOT NULL, price DOUBLE PRECISION NOT NULL, is_active TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE app_user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_88BDF3E9E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, unit_price DOUBLE PRECISION NOT NULL, unit_cost_snapshot DOUBLE PRECISION DEFAULT NULL, status VARCHAR(30) NOT NULL, customer_order_id INT NOT NULL, dish_id INT NOT NULL, INDEX IDX_52EA1F09A15A2E17 (customer_order_id), INDEX IDX_52EA1F09148EB0CB (dish_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE preparation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, yield_portions DOUBLE PRECISION NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE supplier (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, email VARCHAR(150) DEFAULT NULL, phone VARCHAR(40) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE preparation_item (id INT AUTO_INCREMENT NOT NULL, quantity DOUBLE PRECISION NOT NULL, unit VARCHAR(20) NOT NULL, preparation_id INT NOT NULL, ingredient_id INT NOT NULL, INDEX IDX_ADE673DD3DD9B8BA (preparation_id), INDEX IDX_ADE673DD933FE08C (ingredient_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dish_preparation (id INT AUTO_INCREMENT NOT NULL, portions DOUBLE PRECISION NOT NULL, dish_id INT NOT NULL, preparation_id INT NOT NULL, INDEX IDX_2BE19911148EB0CB (dish_id), INDEX IDX_2BE199113DD9B8BA (preparation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock_movement (id INT AUTO_INCREMENT NOT NULL, quantity_delta DOUBLE PRECISION NOT NULL, type VARCHAR(40) NOT NULL, reason VARCHAR(255) NOT NULL, unit_cost_at_movement DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, ingredient_id INT NOT NULL, INDEX IDX_BB1BC1B5933FE08C (ingredient_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE menu_dish ADD CONSTRAINT FK_5D327CF6CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menu_dish ADD CONSTRAINT FK_5D327CF6148EB0CB FOREIGN KEY (dish_id) REFERENCES dish (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dish_ingredient ADD CONSTRAINT FK_77196056148EB0CB FOREIGN KEY (dish_id) REFERENCES dish (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dish_ingredient ADD CONSTRAINT FK_77196056933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
        $this->addSql('ALTER TABLE ingredient ADD CONSTRAINT FK_6BAF78702ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09A15A2E17 FOREIGN KEY (customer_order_id) REFERENCES customer_order (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09148EB0CB FOREIGN KEY (dish_id) REFERENCES dish (id)');
        $this->addSql('ALTER TABLE preparation_item ADD CONSTRAINT FK_ADE673DD3DD9B8BA FOREIGN KEY (preparation_id) REFERENCES preparation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE preparation_item ADD CONSTRAINT FK_ADE673DD933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
        $this->addSql('ALTER TABLE dish_preparation ADD CONSTRAINT FK_2BE19911148EB0CB FOREIGN KEY (dish_id) REFERENCES dish (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE dish_preparation ADD CONSTRAINT FK_2BE199113DD9B8BA FOREIGN KEY (preparation_id) REFERENCES preparation (id)');
        $this->addSql('ALTER TABLE stock_movement ADD CONSTRAINT FK_BB1BC1B5933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE stock_movement');
        $this->addSql('DROP TABLE dish_preparation');
        $this->addSql('DROP TABLE preparation_item');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE menu_dish');
        $this->addSql('DROP TABLE dish_ingredient');
        $this->addSql('DROP TABLE ingredient');
        $this->addSql('DROP TABLE supplier');
        $this->addSql('DROP TABLE preparation');
        $this->addSql('DROP TABLE dish');
        $this->addSql('DROP TABLE menu');
        $this->addSql('DROP TABLE customer_order');
        $this->addSql('DROP TABLE app_user');
    }
}
