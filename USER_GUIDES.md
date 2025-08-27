# WL School - User Guides

## Table of Contents

1. [User Registration Guide](#user-registration-guide)
2. [Password Recovery Guide](#password-recovery-guide)
3. [Role and Permission Management Guide](#role-and-permission-management-guide)
4. [Troubleshooting](#troubleshooting)

---

## User Registration Guide

### Overview

This guide walks you through the process of creating a new user account in the WL School Management System.

### Prerequisites

- Valid school subdomain (provided by your school administrator)
- Valid email address
- Access to your email for verification (if enabled)

### Step-by-Step Registration Process

#### 1. Access the Registration Page

- Navigate to your school's login page: `https://[school-subdomain].wlschool.com/register`
- Or click the "Register" link from the login page

#### 2. Fill Out the Registration Form

**Required Information:**

- **School Subdomain**: Your school's unique identifier (e.g., "westfield-high")
- **First Name**: Your first name (maximum 100 characters)
- **Last Name**: Your last name (maximum 100 characters)
- **Email Address**: Valid email address (maximum 255 characters)
- **Password**: Secure password (minimum 8 characters)
- **Confirm Password**: Re-enter your password for confirmation

**Optional Information:**

- **Phone Number**: Your contact phone number (maximum 20 characters)
- **Role**: Select your role (defaults to "student" if not specified)

#### 3. Password Requirements

- Minimum 8 characters
- Should include a mix of:
  - Uppercase letters (A-Z)
  - Lowercase letters (a-z)
  - Numbers (0-9)
  - Special characters (!@#$%^&*)

#### 4. Submit Registration

- Review all information for accuracy
- Click the "Register" button
- Wait for the system to process your registration

#### 5. Email Verification (if enabled)

- Check your email inbox for a verification message
- Click the verification link in the email
- Return to the login page to access your account

### Registration Success

Upon successful registration, you will:

- Receive a confirmation message
- Be automatically logged in (in most cases)
- Have access to your assigned role's features
- Receive a JWT token for API access

### Common Registration Issues

| Issue | Solution |
|-------|----------|
| "School not found" | Verify the school subdomain with your administrator |
| "Email already exists" | Use a different email or contact support if this is your email |
| "School subscription expired" | Contact your school administrator |
| "Password too weak" | Use a stronger password meeting the requirements |
| "Role not found" | Contact your administrator to verify available roles |

---

## Password Recovery Guide

### Overview

If you've forgotten your password, you can reset it using the password recovery system.

### Step-by-Step Password Recovery

#### 1. Access the Password Recovery Page

- Go to your school's login page
- Click "Forgot Password?" link
- Or navigate directly to: `https://[school-subdomain].wlschool.com/password/reset`

#### 2. Request Password Reset

- Enter your registered email address
- Enter your school subdomain
- Click "Send Reset Link"
- Check your email for the reset instructions

#### 3. Reset Your Password

- Open the email from WL School
- Click the "Reset Password" link
- You'll be redirected to a secure reset page
- Enter your new password (must meet security requirements)
- Confirm your new password
- Click "Reset Password"

#### 4. Login with New Password

- Return to the login page
- Use your email and new password to log in
- Update your password in any saved password managers

### Password Reset Security

- Reset links expire after 60 minutes for security
- Links can only be used once
- If the link expires, request a new reset
- Always use a strong, unique password

### Troubleshooting Password Recovery

| Issue | Solution |
|-------|----------|
| "Email not found" | Verify email address and school subdomain |
| "Reset link expired" | Request a new password reset |
| "Link already used" | Request a new password reset if needed |
| "Email not received" | Check spam folder, verify email address |

---

## Role and Permission Management Guide

### Overview

The WL School system uses role-based access control (RBAC) to manage user permissions and access levels.

### Understanding Roles and Permissions

#### What are Roles?

Roles are collections of permissions that define what a user can do in the system. Common roles include:

- **Super Admin**: Full system access (cannot be modified)
- **Admin**: School-level administrative access
- **Teacher**: Classroom and student management
- **Student**: Limited access to personal information and assignments
- **Parent**: Access to child's information and progress
- **Staff**: Administrative support functions

#### What are Permissions?

Permissions are specific actions users can perform, such as:

- `view-users`: View user profiles
- `edit-users`: Modify user information
- `delete-users`: Remove users from the system
- `manage-grades`: Enter and modify grades
- `view-reports`: Access reporting features

### For School Administrators

#### Managing User Roles

##### Viewing User Roles

1. Navigate to **Users** → **User Management**
2. Select a user from the list
3. Click **View Roles** to see current role assignments
4. Review the user's permissions and access levels

##### Assigning Roles to Users

1. Go to **Users** → **User Management**
2. Select the user you want to modify
3. Click **Edit Roles**
4. Select one or more roles from the available list:
   - Use Ctrl+Click (Windows) or Cmd+Click (Mac) for multiple selections
5. Click **Save Changes**
6. Confirm the role assignment

**Example API Request:**
```bash
curl -X POST http://localhost:8001/api/v1/users/123/roles \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "roles": ["teacher", "admin"]
  }'
```

##### Removing Roles from Users

1. Follow steps 1-3 above
2. Deselect the roles you want to remove
3. Ensure the user retains at least one role
4. Click **Save Changes**

#### Managing Role Permissions

##### Viewing Role Permissions

1. Navigate to **Settings** → **Role Management**
2. Select a role from the list
3. Click **View Permissions**
4. Review all assigned permissions

##### Modifying Role Permissions

1. Go to **Settings** → **Role Management**
2. Select the role to modify
3. Click **Edit Permissions**
4. Select/deselect permissions as needed:
   - Permissions are grouped by module (Users, Grades, Reports, etc.)
5. Click **Save Changes**
6. Confirm the permission updates

**Example API Request:**
```bash
curl -X POST http://localhost:8001/api/v1/roles/1/permissions \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "permissions": ["view-users", "edit-users", "manage-grades"]
  }'
```

**⚠️ Important Notes:**
- Super Admin role cannot be modified
- Changes take effect immediately
- Users may need to log out and back in to see changes
- Always test permission changes with a test account first

### For End Users

#### Checking Your Permissions

1. Log into your account
2. Go to **Profile** → **My Account**
3. Click **View My Roles**
4. Review your assigned roles and permissions

#### Understanding Your Access Level

Your role determines what you can see and do:

- **Navigation Menu**: Only shows sections you have access to
- **Action Buttons**: Disabled if you lack permissions
- **Data Visibility**: Filtered based on your role and school

#### Requesting Additional Permissions

1. Identify what additional access you need
2. Contact your school administrator
3. Provide a business justification for the request
4. Wait for approval and role assignment

### Best Practices for Role Management

#### For Administrators

1. **Principle of Least Privilege**
   - Give users only the minimum permissions needed
   - Regularly review and audit user roles
   - Remove unnecessary permissions promptly

2. **Role Standardization**
   - Create standard roles for common positions
   - Document role purposes and permissions
   - Use consistent naming conventions

3. **Regular Audits**
   - Review user roles quarterly
   - Remove roles from inactive users
   - Update roles when job responsibilities change

4. **Testing**
   - Test role changes with non-production accounts
   - Verify permissions work as expected
   - Document any issues or limitations

#### For Users

1. **Security Awareness**
   - Don't share login credentials
   - Report suspicious activity immediately
   - Log out when finished using the system

2. **Permission Requests**
   - Be specific about what access you need
   - Explain why you need additional permissions
   - Follow your school's approval process

### Common Role Scenarios

#### Scenario 1: New Teacher Setup

**Roles to Assign:**
- `teacher` (primary role)
- `staff` (if administrative duties required)

**Key Permissions:**
- View and manage assigned classes
- Enter and modify grades
- Communicate with students and parents
- Access curriculum materials

#### Scenario 2: Department Head

**Roles to Assign:**
- `teacher` (teaching responsibilities)
- `admin` (departmental oversight)

**Key Permissions:**
- All teacher permissions
- View department reports
- Manage department staff
- Access budget information

#### Scenario 3: Substitute Teacher

**Roles to Assign:**
- `substitute` (temporary access)

**Key Permissions:**
- View class rosters
- Take attendance
- Access lesson plans
- Limited grade entry

---

## Troubleshooting

### Common Issues and Solutions

#### Authentication Issues

| Problem | Symptoms | Solution |
|---------|----------|----------|
| Can't log in | "Invalid credentials" error | Verify email, password, and school subdomain |
| Account locked | "Too many attempts" message | Wait 5 minutes or contact administrator |
| Token expired | Automatic logout | Log in again or use refresh token |
| School not found | "School not found" error | Verify subdomain with administrator |

#### Permission Issues

| Problem | Symptoms | Solution |
|---------|----------|----------|
| Missing menu items | Can't see expected features | Check role assignments with administrator |
| "Access Denied" errors | 403 errors when accessing features | Request additional permissions |
| Can't modify data | Read-only access to information | Verify edit permissions for your role |

#### Registration Issues

| Problem | Symptoms | Solution |
|---------|----------|----------|
| Email already exists | "Email already registered" | Use different email or recover password |
| Invalid school | "School not found" | Verify subdomain spelling and status |
| Weak password | "Password requirements not met" | Use stronger password with mixed characters |
| Subscription expired | "School subscription expired" | Contact school administrator |

### Getting Help

#### Contact Information

- **Technical Support**: support@wlschool.com
- **School Administrator**: Contact your local school admin
- **Documentation**: Check this guide and API documentation
- **Emergency Access**: Contact your school's IT department

#### When Contacting Support

Please provide:

1. **User Information**
   - Your email address
   - School subdomain
   - Role assignments

2. **Issue Details**
   - What you were trying to do
   - Error messages received
   - Steps to reproduce the problem

3. **System Information**
   - Browser and version
   - Operating system
   - Time when issue occurred

#### Self-Help Resources

- **API Documentation**: `/api/documentation`
- **System Status**: Check service health endpoints
- **User Forums**: Community support and discussions
- **Video Tutorials**: Available in the help section

---

## Appendix

### Glossary

- **JWT**: JSON Web Token used for authentication
- **RBAC**: Role-Based Access Control system
- **Subdomain**: Unique school identifier (e.g., "westfield" in westfield.wlschool.com)
- **Permission**: Specific action a user can perform
- **Role**: Collection of permissions assigned to users
- **Multi-tenancy**: System supporting multiple schools with isolated data

### Quick Reference

#### Default Roles and Permissions

| Role | Key Permissions |
|------|----------------|
| Super Admin | All permissions (system-wide) |
| Admin | School management, user management, reports |
| Teacher | Class management, grading, student communication |
| Student | View personal information, assignments, grades |
| Parent | View child's information, communicate with teachers |
| Staff | Administrative support, limited user management |

#### API Endpoints Quick Reference

| Action | Method | Endpoint |
|--------|--------|----------|
| Login | POST | `/api/v1/auth/login` |
| Register | POST | `/api/v1/auth/register` |
| Get Profile | GET | `/api/v1/auth/me` |
| Assign Roles | POST | `/api/v1/users/{id}/roles` |
| Get User Roles | GET | `/api/v1/users/{id}/roles` |
| Assign Permissions | POST | `/api/v1/roles/{id}/permissions` |

---

*Last Updated: January 2024*
*Version: 1.0.0*