# Quick Response Coordination System (QRCS) 🚨

## Overview 🌍
Quick Response Coordination System (QRCS) is a comprehensive disaster management platform designed to streamline emergency response coordination. This system bridges the gap between users reporting emergencies and administrators who manage resources and coordinate response efforts.

## Key Features ✨

### User Features 👥

* **Emergency Management** 🆘
  * Deploy new emergency alerts with detailed information
  * Delete emergency requests when resolved or no longer relevant
  
* **Profile Management** 👤
  * Update personal information
  * Manage contact details and preferences
  
* **Notifications** 📱
  * Receive real-time system alerts
  * View updates from administrators
  * Track emergency response progress

### Admin Features 👑

* **Emergency Response Management** 📋
  * Review incoming emergency requests
  * Accept or reject requests based on validity
  * Coordinate appropriate response actions
  
* **Resource Management** 🚑
  * Create new resources (ambulances, fire trucks, rescue teams, etc.)
  * Edit resource details (type, capacity, capabilities)
  * Mark resources as under maintenance or unavailable
  * Delete resources when necessary
  * Dispatch appropriate resources to incident sites
  
* **Communication** 📢
  * Send updates and notifications to users
  * Broadcast emergency alerts to specific areas
  
* **Reporting and Analytics** 📊
  * Generate comprehensive incident reports
  * Create resource utilization statistics
  * Export data for further analysis

## Tech Stack 💻

* **Frontend**: 
  * HTML5
  * Tailwind CSS
  * JavaScript
  
* **Backend**:
  * PHP
  
* **Database**:
  * MySQL

## Installation 🔧

1. Clone the repository
   ```
   git clone https://github.com/kartik-srvt147/Quick-Response-Co-Ordination-System.git
   ```

2. Set up XAMPP environment
   * Install XAMPP if not already installed
   * Start Apache and MySQL services

3. Configure the database
   * Place project files in XAMPP's htdocs directory
   * Run the following PHP files in sequence through your localhost:
     * `db_setup.php` - Initial database setup
     * `update_db.php` - Core database structure
     * `update_db_phone.php` - Phone number validation structures
     * `update_db_status.php` - Status tracking tables
     * `db_notifications_setup.php` - Notification system setup

4. Configure database connection
5. Access the application

## Usage Guide 📝

### For Users
1. Register for a new account
2. Login to the user dashboard
3. To report an emergency:
   * Click "Report Emergency"
   * Fill in required details (location, type, severity)
   * Submit request
4. Monitor notifications for updates

### For Administrators
1. Login to admin portal
2. Review emergency requests
3. Manage resources:
   * Add new resources
   * Update resource status
   * Dispatch resources to emergencies
4. Generate reports for analysis

## Security 🔒

* Role-based access control
* Encrypted data transmission
* Session management
* Input validation

## Future Enhancements 🚀

* Mobile application integration
* Real-time GPS tracking of resources
* Integration with weather alert systems
* AI-based resource allocation recommendations
* Multi-language support

## Contact 📧

For questions or support, please contact:
* Email: kartik.srvt.147@gmail.com
* LinkedIn: https://www.linkedin.com/in/kartikeya20/

---

⚠️ **Disclaimer**: This system is designed to augment existing emergency services, not replace them. Always call your local emergency number for immediate life-threatening emergencies.
