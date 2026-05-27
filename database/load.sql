/*
INSERT INTO Customers (Email,FirstName,LastName,Title,Address,City,State,Country,PostCode,Phone,Admin)
VALUES ("ex123@gmail.com", "John", "Doe", "Mr.", "24 Faker Avenue", "Darwin", "NT", "Australia", 0800, 0412345678, 0)
*/

-- localhost/phpmyadmin - setup the db here


USE ecommerce_db;

-- Customers



INSERT INTO Customers
(Email, FirstName, LastName, Title,
Address, City, State, Country,
PostCode, Phone)

VALUES 
(
    "johndoetest@example.com",
    "John",
    "Doe",
    "Mr.",
    "24 Faker Avenue",
    "Darwin",
    "NT",
    "Australia",
    "0800",
    "0412345678"
);



-- Products



INSERT INTO Products
(
    Title, Description, Price, Category, Image
)

VALUES 
(
    "Sunset Landscape",
    "Oil painting inspired by Darwin Sunsets",
    199.99,
    "Painting",
    "sunset.jpg"
),

(
    "Ocean Sculpture",
    "Handcrafted ocean themed sculpture",
    349.50,
    "Sculpture",
    "ocean.jpg"
),

(
    "Abstract Sketch",
    "Modern abstract pencil drawing",
    89.99,
    "Drawing",
    "abstract.jpg"
);



-- Purchases



INSERT INTO Purchases 
(PurchaseDate, CustomerID)

VALUES
(
    NOW(),
    1
);



-- Purchase Items



INSERT INTO PurchaseItem
(PurchaseID, ProductID, Quantity)

VALUES
(1,1,1),
(1,2,2);