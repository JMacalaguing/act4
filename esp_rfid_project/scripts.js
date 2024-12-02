 // Function to fetch the latest RFID data
 function fetchLatestRFID() {
    fetch('query.php') // Fetch from the PHP script
    .then(response => response.json())
    .then(data => {
        // Check if the data contains RFID and status
        if (data.rfid) {
            // Update the displayed RFID value and status as 1 or 0
            document.getElementById('latest-rfid').innerText = data.rfid;
            document.getElementById('status-message').innerText = data.status === 'RFID not found' ? '0' : data.status;
        } else {
            document.getElementById('latest-rfid').innerText = 'No RFID data available';
            document.getElementById('status-message').innerText = '0';
        }
    })
    .catch(error => console.error('Error fetching RFID ', error));
}

// Set interval to fetch latest RFID data every 5 seconds
setInterval(fetchLatestRFID, 5000); // Adjust the interval as needed

