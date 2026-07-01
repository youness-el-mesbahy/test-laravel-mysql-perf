-- Laravel/MySQL Performance Testing Lab
-- T0.6: Queries to test if dataset size impacts query times

-- Query 1: Expensive Search and Joins (Unindexed filter on description and country)
-- Description: Find customers from a specific country who bought products containing a keyword in the description.
-- Why it hurts: No index on country, description is a TEXT field (full table scans), and multiple joins.
SELECT 
    c.id AS customer_id, 
    c.name AS customer_name, 
    c.country, 
    o.id AS order_id, 
    p.name AS product_name, 
    oi.quantity, 
    oi.subtotal
FROM customers c
JOIN orders o ON o.customer_id = c.id
JOIN order_items oi ON oi.order_id = o.id
JOIN products p ON p.id = oi.product_id
WHERE c.country = 'Canada'
  AND p.description LIKE '%voluptas%'
ORDER BY oi.subtotal DESC
LIMIT 50;

-- Query 2: Product Sales Aggregation with Unindexed Date Filter & Sorting
-- Description: Rank products by total units sold within a specific date range.
-- Why it hurts: Joining 100k orders, 250k order_items, and grouping/sorting on large dataset.
SELECT 
    p.id AS product_id, 
    p.name AS product_name, 
    SUM(oi.quantity) AS total_units_sold, 
    SUM(oi.subtotal) AS total_revenue
FROM products p
JOIN order_items oi ON oi.product_id = p.id
JOIN orders o ON o.id = oi.order_id
WHERE o.order_date BETWEEN '2025-09-01 00:00:00' AND '2025-11-30 23:59:59'
GROUP BY p.id, p.name
ORDER BY total_units_sold DESC
LIMIT 20;

-- Query 3: Correlated Subquery / High-Cost Comparison
-- Description: Find all orders that are significantly larger than the average order total in their customer's country.
-- Why it hurts: Correlated subquery executing against a grouped dataset with no indexes on 'country'.
SELECT 
    o.id AS order_id, 
    o.customer_id, 
    c.country, 
    o.total_amount
FROM orders o
JOIN customers c ON o.customer_id = c.id
WHERE o.total_amount > (
    SELECT AVG(o2.total_amount) * 1.5
    FROM orders o2
    JOIN customers c2 ON o2.customer_id = c2.id
    WHERE c2.country = c.country
)
ORDER BY o.total_amount DESC
LIMIT 50;
