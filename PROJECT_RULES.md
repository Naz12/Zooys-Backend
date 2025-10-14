# Zooys Backend Laravel - Project Rules

## Database Configuration Rules

### 🚨 CRITICAL: Database Requirements

**MANDATORY DATABASE**: This project MUST use **MySQL** as the primary database.

#### ❌ PROHIBITED DATABASES
- **SQLite** - NOT ALLOWED for production or development
- **PostgreSQL** - NOT ALLOWED without explicit project approval
- **SQL Server** - NOT ALLOWED without explicit project approval
- **MongoDB** - NOT ALLOWED without explicit project approval

#### ✅ REQUIRED DATABASE
- **MySQL** - MANDATORY for all environments (development, staging, production)

### Database Configuration Standards

#### Required .env Settings
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zooys_backend
DB_USERNAME=root
DB_PASSWORD=
```

#### Database Setup Requirements
1. **XAMPP/WAMP/LAMP**: MySQL service must be running
2. **Database Creation**: Ensure `zooys_backend` database exists
3. **Migrations**: Run `php artisan migrate` after database setup
4. **Seeding**: Run `php artisan db:seed` for initial data

### Development Environment Setup

#### Prerequisites
- XAMPP with MySQL service running
- MySQL accessible on port 3306
- Database `zooys_backend` created

#### Setup Commands
```bash
# Start MySQL service (XAMPP)
# Create database: zooys_backend

# Run migrations
php artisan migrate

# Run seeders (if needed)
php artisan db:seed
```

### Error Handling for Database Issues

#### Common Issues & Solutions

1. **"Connection refused" Error**
   - **Cause**: MySQL service not running
   - **Solution**: Start MySQL service in XAMPP/WAMP

2. **"Database doesn't exist" Error**
   - **Cause**: Database `zooys_backend` not created
   - **Solution**: Create database in phpMyAdmin or MySQL command line

3. **"Access denied" Error**
   - **Cause**: Wrong username/password
   - **Solution**: Verify DB_USERNAME and DB_PASSWORD in .env

### Code Standards

#### Database-Related Code
- All models must use MySQL-compatible syntax
- Use Laravel's Eloquent ORM for database operations
- Avoid raw SQL queries unless absolutely necessary
- Use database transactions for complex operations

#### Migration Guidelines
- All database changes must be in migration files
- Never modify database structure directly
- Use descriptive migration names
- Include rollback functionality

### Monitoring & Maintenance

#### Database Health Checks
- Regular backup procedures
- Monitor database performance
- Check for slow queries
- Maintain proper indexing

#### Security Requirements
- Use environment variables for database credentials
- Never commit database passwords to version control
- Use prepared statements for all queries
- Implement proper access controls

## Enforcement

### Code Review Checklist
- [ ] Database configuration uses MySQL only
- [ ] No SQLite or other database references
- [ ] Proper .env configuration
- [ ] Database migrations are present
- [ ] No hardcoded database credentials

### Deployment Requirements
- MySQL service must be available
- Database must be properly configured
- Migrations must be run successfully
- Database connection must be tested

---

**Note**: Any deviation from these database rules requires explicit approval from the project lead and must be documented with justification.
