USE ecommerce_db;

DROP TABLE IF EXISTS Purchases;
DROP TABLE IF EXISTS PurchaseItem;
DROP TABLE IF EXISTS Products;
DROP TABLE IF EXISTS Customers;

CREATE TABLE Customers(
    CustomerID INT NOT NULL AUTO_INCREMENT, -- added NOT NULL since its a PK
    Email VARCHAR(255) NOT NULL,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Title ENUM("Mr.","Mrs.","Mx.","Ms."),
    Address VARCHAR(75) NOT NULL, -- increased address length
    City VARCHAR(50) NOT NULL,
    State VARCHAR(50) NOT NULL,
    Country VARCHAR(50) NOT NULL,
    PostCode VARCHAR(10) NOT NULL, -- Changed to VARCHAR since leading zeroes might get stripped
    Phone VARCHAR(15) NOT NULL, -- Increased size, since area code can increase it or spaces can take an index

    PRIMARY KEY (CustomerID)
    -- remove customer logins. Purchasing order no need for --> Email details etc......
    -- I Removed the admin bool as i think we go ahead with no user accounts.
);

CREATE TABLE Products(
    ProductID INT NOT NULL AUTO_INCREMENT,
    Title VARCHAR(100) NOT NULL, -- added title so we dont need to put it in the description also
    Description TEXT NOT NULL,
    Price DECIMAL(10,2) NOT NULL, -- changed from INT to decimal since price can be a float and money generally is like $100.99
    Category ENUM("Painting","Sculpture","Drawing") NOT NULL,
    Image VARCHAR(255) DEFAULT 'placeholder.jpg', -- image so we can store img file, put a placeholder in case of empty entry [not img binary data]

    PRIMARY KEY (ProductID)
);

CREATE TABLE Purchases(
    PurchaseID INT NOT NULL AUTO_INCREMENT,
    CustomerID INT NOT NULL, -- added NOT NULL
    PurchaseDate DATETIME NOT NULL, -- altered attribute to match entity
    
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