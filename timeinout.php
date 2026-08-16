<!DOCTYPE html>
<html>
<head>
  <title>QR Timekeeping</title>
</head>
<body>
  <h2>Scan QR to Time In/Out</h2>
  <video id="preview" width="400" height="300"></video>

  <!-- Use your local Instascan JS -->
  <script src="http://localhost/elcajaritocafe/js/instascan.min.js"></script>
  <script>
    let scanner = new Instascan.Scanner({ video: document.getElementById('preview') });
    scanner.addListener('scan', function (content) {
      fetch('time_log.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'employee_id=' + content
      })
      .then(response => response.text())
      .then(data => alert(data));
    });
    Instascan.Camera.getCameras().then(function (cameras) {
      if (cameras.length > 0) {
        scanner.start(cameras[0]);
      } else {
        alert('No cameras found.');
      }
    }).catch(function (e) {
      console.error(e);
    });
  </script>
</body>
</html>
