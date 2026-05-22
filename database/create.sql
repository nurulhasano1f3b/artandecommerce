USE ecommerce_db;

DROP TABLE IF EXISTS Purchases;
DROP TABLE IF EXISTS PurchaseItem;
DROP TABLE IF EXISTS Products;
DROP TABLE IF EXISTS Customers;

CREATE TABLE Customers(
    CustomerID INT AUTO_INCREMENT,
    Email VARCHAR(255) NOT NULL,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Title ENUM("Mr.","Mrs.","Mx.","Ms."),
    Address VARCHAR(50) NOT NULL,
    City VARCHAR(25) NOT NULL,
    State VARCHAR(25) NOT NULL,
    Country VARCHAR(25) NOT NULL,
    PostCode INT(4) NOT NULL,
    Phone VARCHAR(12) NOT NULL,
    Admin BOOL NOT NULL,
    PRIMARY KEY (CustomerID)
);

CREATE TABLE Products(
    ProductID INT AUTO_INCREMENT,
    Description VARCHAR(50) NOT NULL,
    Price INT(9) NOT NULL,
    Category ENUM("Painting","Sculpture","Drawing"),
    PRIMARY KEY (ProductID)
);

CREATE TABLE Purchases(
    PurchaseID INT AUTO_INCREMENT,
    Date DATETIME NOT NULL,
    CustomerID INT,
    PRIMARY KEY (PurchaseID),
    CONSTRAINT fk_Customer
    FOREIGN KEY (CustomerID)
    REFERENCES Customers(CustomerID)
);

CREATE TABLE PurchaseItem(
    PurchaseID INT NOT NULL,
    ProductID INT NOT NULL,
    Quantity INT(5) NOT NULL,
    PRIMARY KEY (PurchaseID, ProductID),
    CONSTRAINT fk_Purchase
    FOREIGN KEY (PurchaseID)
    REFERENCES Purchases(PurchaseID),
    CONSTRAINT fk_Product
    FOREIGN KEY (ProductID)
    REFERENCES Products(ProductID)
);