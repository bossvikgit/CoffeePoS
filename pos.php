<?php 
include 'db.php'; 
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Optional: restrict POS to certain roles
// Example: allow both admin and cashier, but block others
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'cashier') {
    echo "<h2>Access Denied. You do not have permission to use the POS.</h2>";
    exit;
}

?>
<link rel="stylesheet" type="text/css" href="style.css">

<header>
    <h1>EL Cajarito Cafe - POS</h1>
</header>
<nav>
    <a href="inventory.php">Inventory</a>
    <a href="pos.php">POS</a>
    <a href="report.php">Reports</a>
    <a href="login.php">Logout (<?php echo $_SESSION['user']; ?>)</a>
</nav>

<h2>Point of Sale</h2>

<style>
.container {
    display: flex;
    justify-content: space-between;
    margin: 20px;
}
.left-panel, .right-panel {
    width: 48%;
}
.products {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
}
.product-card {
    background-color: #4e342e;
    border: 1px solid #8d6e63;
    border-radius: 8px;
    margin: 10px;
    padding: 10px;
    text-align: center;
    width: 150px;
}
.product-card img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border: 1px solid #8d6e63;
    border-radius: 5px;
}
.cart-table {
    width: 100%;
    border-collapse: collapse;
}
.cart-table th, .cart-table td {
    border: 1px solid #8d6e63;
    padding: 10px;
    text-align: center;
}
</style>

<div class="container">
    <!-- LEFT PANEL: Cart & Checkout -->
    <div class="left-panel">
        <?php
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Add to cart
        if (isset($_POST['add_to_cart'])) {
            $item_id = $_POST['item_id'];
            $qty = $_POST['quantity'];
            $item = $conn->query("SELECT * FROM inventory WHERE id=$item_id")->fetch_assoc();

            if ($item['stock'] >= $qty) {
                $_SESSION['cart'][] = [
                    'id' => $item['id'],
                    'name' => $item['item_name'],
                    'price' => $item['price'],
                    'qty' => $qty,
                    'image' => $item['image']
                ];
            } else {
                echo "<p style='color:red;'>Not enough stock for {$item['item_name']}!</p>";
            }
        }

        // Remove from cart
        if (isset($_POST['remove_item'])) {
            $index = $_POST['index'];
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // reindex
        }

        // Display cart
        if (!empty($_SESSION['cart'])) {
            echo "<h2>Order Cart</h2>";
            echo "<table class='cart-table'>
                    <tr><th>Image</th><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th><th>Action</th></tr>";
            $total = 0;
            foreach ($_SESSION['cart'] as $i => $c) {
                $subtotal = $c['price'] * $c['qty'];
                $total += $subtotal;
                echo "<tr>
                        <td><img src='{$c['image']}' width='60'></td>
                        <td>{$c['name']}</td>
                        <td>{$c['qty']}</td>
                        <td>₱{$c['price']}</td>
                        <td>₱$subtotal</td>
                        <td>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='index' value='$i'>
                                <button type='submit' name='remove_item'>Remove</button>
                            </form>
                        </td>
                      </tr>";
            }
            echo "</table>";

            // VAT (captured only) and Service Charge
            $vat = $total * 0.12; // captured for report only
            $net_total = $total;  // exclude VAT from payment
            $service_charge = $net_total * 0.10;
            $grand_total = $net_total + $service_charge;

            echo "<h3>Total Items: ₱$total</h3>";
            echo "<h3>Captured VAT (12%): ₱$vat</h3>";
            echo "<h3>10% Service Charge: ₱$service_charge</h3>";
            echo "<h2 style='color:#2e7d32;'>Grand Total (Payment): ₱$grand_total</h2>";

            // Payment form
            echo "<form method='POST'>
                    <select name='payment_method'>
                        <option value='Cash'>Cash</option>
                        <option value='Credit Card'>Credit Card</option>
                        <option value='GCash'>GCash</option>
                        <option value='Maya'>Maya</option>
                    </select>
                    <button type='submit' name='checkout'>Checkout</button>
                  </form>";
        }

        // Checkout
        if (isset($_POST['checkout'])) {
            $payment = $_POST['payment_method'];
            $receipt_id = uniqid("RCPT-");
            $total = 0;

            foreach ($_SESSION['cart'] as $c) {
                $subtotal = $c['price'] * $c['qty'];
                $total += $subtotal;
                $conn->query("INSERT INTO sales (item_id, quantity, total, vat, service_charge, payment_method, receipt_id) 
                            VALUES ('{$c['id']}', '{$c['qty']}', '$subtotal', '$vat', '$service_charge', '$payment', '$receipt_id')");
                $conn->query("UPDATE inventory SET stock=stock-{$c['qty']} WHERE id={$c['id']}");
            }

            $_SESSION['last_receipt'] = $receipt_id;

            // Print-friendly popup
            echo "<script>
                var receiptWindow = window.open('', 'PrintReceipt', 'width=400,height=600');
                receiptWindow.document.write('<html><head><title>Receipt</title></head><body style=\"font-family:Arial;\">');
                receiptWindow.document.write('<h2 style=\"text-align:center;\">EL Cajarito Cafe - Receipt</h2>');
                receiptWindow.document.write('<p>Receipt ID: $receipt_id</p>');
                receiptWindow.document.write('<p>Payment Method: $payment</p>');
                receiptWindow.document.write('<p>Total Items: ₱$total</p>');
                receiptWindow.document.write('<p>Captured VAT (12%): ₱$vat</p>');
                receiptWindow.document.write('<p>Service Charge (10%): ₱$service_charge</p>');
                receiptWindow.document.write('<h3>Grand Total (Payment): ₱" . ($total + $service_charge) . "</h3>');
                receiptWindow.document.write('<button onclick=\"window.print()\">Print</button>');
                receiptWindow.document.write('</body></html>');
                receiptWindow.document.close();
    </script>";

            // Automatically clear cart after checkout
            $_SESSION['cart'] = [];
            echo "<p style='color:green;'>Checkout complete! Cart cleared. Ready for new order.</p>";
        }

        // Reprint last receipt
        if (isset($_POST['reprint'])) {
            $receipt_id = $_SESSION['last_receipt'];
            $result = $conn->query("SELECT sales.*, inventory.item_name 
                                    FROM sales 
                                    JOIN inventory ON sales.item_id=inventory.id 
                                    WHERE receipt_id='$receipt_id'");
            $total = 0;
            $vat = 0;
            $service_charge = 0;
            while ($row = $result->fetch_assoc()) {
                $total += $row['total'];
                $vat = $row['vat'];
                $service_charge = $row['service_charge'];
            }
            $grand_total = $total + $service_charge; // exclude VAT from payment

            echo "<script>
                var receiptWindow = window.open('', 'PrintReceipt', 'width=400,height=600');
                receiptWindow.document.write('<html><head><title>Receipt</title></head><body style=\"font-family:Arial;\">');
                receiptWindow.document.write('<h2 style=\"text-align:center;\">Coffee Shop POS</h2>');
                receiptWindow.document.write('<p>Reprint Receipt ID: $receipt_id</p>');
                receiptWindow.document.write('<p>Total Items: ₱$total</p>');
                receiptWindow.document.write('<p>Captured VAT (12%): ₱$vat</p>');
                receiptWindow.document.write('<p>Service Charge (10%): ₱$service_charge</p>');
                receiptWindow.document.write('<h3>Grand Total (Payment): ₱$grand_total</h3>');
                receiptWindow.document.write('<button onclick=\"window.print()\">Print</button>');
                receiptWindow.document.write('</body></html>');
                receiptWindow.document.close();
            </script>";
        }

        if (isset($_SESSION['last_receipt'])) {
            echo "<form method='POST'><button type='submit' name='reprint'>Reprint Last Receipt</button></form>";
        }
        ?>
    </div>

    <!-- RIGHT PANEL: Product Selection with Keyword Filter -->
    <div class="right-panel">
        <h2>Select Items</h2>
        <form method="GET" style="text-align:center; margin-bottom:15px;">
            <input type="text" name="keyword" placeholder="Search items..." value="<?php echo isset($_GET['keyword']) ? $_GET['keyword'] : ''; ?>">
            <button type="submit">Search</button>
        </form>

        <div class="products">
        <?php
        $filter = "";
        if (!empty($_GET['keyword'])) {
            $kw = $conn->real_escape_string($_GET['keyword']);
            $filter = "WHERE item_name LIKE '%$kw%' OR category LIKE '%$kw%'";
        }
        $items = $conn->query("SELECT * FROM inventory $filter");
        while ($row = $items->fetch_assoc()) {
            echo "<div class='product-card'>
                    <form method='POST'>
                        <img src='{$row['image']}' alt='{$row['item_name']}'>
                        <p>{$row['item_name']}</p>
                        <p>₱{$row['price']}</p>
                        <input type='hidden' name='item_id' value='{$row['id']}'>
                        <input type='number' name='quantity' value='1' min='1' style='width:60px;'>
                        <button type='submit' name='add_to_cart'>Add</button>
                    </form>
                  </div>";
        }
        ?>
        </div>
    </div>
</div>
