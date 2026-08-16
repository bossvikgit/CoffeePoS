<?php include 'db.php'; 

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
    <h1>Coffee Shop POS</h1>
</header>
<nav>
    <a href="inventory.php">Inventory</a>
    <a href="pos.php">POS</a>
    <a href="report.php">Reports</a>
    <a href="login.php">Logout (<?php echo $_SESSION['user']; ?>)</a>
</nav>

<h2>Inventory Management</h2>

<!-- Add Item Form -->
<form method="POST" enctype="multipart/form-data">
    <input type="text" name="item_name" placeholder="Item Name" required>
    <input type="number" step="0.01" name="price" placeholder="Price" required>
    <input type="number" name="stock" placeholder="Stock" required>
    <input type="file" name="image" accept="image/*" required>
    <button type="submit" name="add">Add Item</button>
</form>

<?php
// ADD ITEM
if (isset($_POST['add'])) {
    $name = $_POST['item_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);

    $conn->query("INSERT INTO inventory (item_name, price, stock, image) VALUES ('$name','$price','$stock','$target_file')");
    echo "<p style='color:#FFD700;'>Item added!</p>";
}
// EDIT FORM
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $item = $conn->query("SELECT * FROM inventory WHERE id=$id")->fetch_assoc();
    ?>
    <h2>Edit Item</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
        <input type="text" name="item_name" value="<?php echo $item['item_name']; ?>" required>
        <input type="number" step="0.01" name="price" value="<?php echo $item['price']; ?>" required>
        <input type="number" name="stock" value="<?php echo $item['stock']; ?>" required>
        <input type="file" name="image" accept="image/*">
        <button type="submit" name="update">Update Item</button>
    </form>
    <?php
}
// DELETE ITEM
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM inventory WHERE id=$id");
    echo "<p style='color:#FFD700;'>Item deleted!</p>";
}

// EDIT ITEM
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['item_name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    if (!empty($_FILES["image"]["name"])) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
        $conn->query("UPDATE inventory SET item_name='$name', price='$price', stock='$stock', image='$target_file' WHERE id=$id");
    } else {
        $conn->query("UPDATE inventory SET item_name='$name', price='$price', stock='$stock' WHERE id=$id");
    }
    echo "<p style='color:#FFD700;'>Item updated!</p>";
}

// DISPLAY INVENTORY
$result = $conn->query("SELECT * FROM inventory");
echo "<table>
        <tr>
            <th>Image</th>
            <th>Item</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Actions</th>
        </tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td><img src='{$row['image']}' width='80'></td>
            <td>{$row['item_name']}</td>
            <td>₱{$row['price']}</td>
            <td>{$row['stock']}</td>
            <td>
                <a href='?delete={$row['id']}' style='color:red;'>Delete</a> |
                <a href='?edit={$row['id']}' style='color:#FFD700;'>Edit</a>
            </td>
          </tr>";
}
echo "</table>";


?>
