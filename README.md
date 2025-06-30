# Smart Steps Tutorial - Quiz Management System (PHP Version)

A comprehensive quiz management system for teachers and students, built with PHP and MySQL.

## Features

- **Teacher Registration & Authentication**: Secure teacher accounts with JWT authentication
- **Quiz Creation**: Create quizzes with multiple-choice questions and time limits
- **Student Quiz Taking**: Students can take quizzes using shareable links
- **Real-time Results**: Instant scoring and detailed corrections
- **Performance Analytics**: Track student performance and quiz statistics
- **Responsive Design**: Works on desktop and mobile devices

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for dependency management)

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd smart-steps-tutorial
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   - Copy `.env.example` to `.env`
   - Update database credentials and other settings in `.env`

4. **Set up database**
   - Create a MySQL database
   - Run the installation script: `php install.php`
   - Or manually import the database schema

5. **Configure web server**
   - Point your web server document root to the project directory
   - Ensure `.htaccess` is enabled for Apache
   - For Nginx, configure URL rewriting similar to the `.htaccess` rules

6. **Set permissions**
   ```bash
   chmod 755 public/
   chmod 644 .env
   ```

## Configuration

### Environment Variables (.env)

```env
DB_HOST=localhost
DB_NAME=smart_steps_quiz
DB_USER=your_username
DB_PASS=your_password
JWT_SECRET=your-secret-key
SESSION_SECRET=your-session-secret
```

### Database Schema

The system automatically creates the following tables:
- `teachers` - Teacher accounts and authentication
- `quizzes` - Quiz information and settings
- `quiz_questions` - Individual quiz questions and options
- `student_responses` - Student quiz submissions and scores

## Usage

### For Teachers

1. **Register**: Visit `/teacher-register` to create an account
2. **Login**: Access `/teacher-login` to sign in
3. **Dashboard**: View all quizzes and student responses at `/teacher-dashboard`
4. **Create Quiz**: Add new quizzes at `/create-quiz`
5. **Share Quiz**: Copy the shareable link for students
6. **View Results**: Monitor student performance and detailed analytics

### For Students

1. **Take Quiz**: Use the shareable link provided by teacher
2. **Enter Name**: Provide your name to start the quiz
3. **Answer Questions**: Complete all questions within time limit (if set)
4. **View Results**: See immediate score and percentage
5. **Review Corrections**: Access detailed answer explanations

## API Endpoints

### Authentication
- `POST /api/register` - Teacher registration
- `POST /api/login` - Teacher login
- `POST /api/logout` - Teacher logout
- `GET /api/verify` - Verify authentication token

### Quiz Management
- `POST /api/quiz` - Create new quiz
- `GET /api/quiz` - Get teacher's quizzes
- `DELETE /api/quiz/{id}` - Delete quiz
- `GET /api/quiz/{shareId}` - Get quiz for students (public)

### Quiz Responses
- `POST /api/submit/{shareId}` - Submit quiz answers
- `GET /api/responses/{quizId}` - Get quiz responses (teacher)
- `GET /api/all-responses` - Get all responses (teacher)
- `GET /api/quiz-stats/{quizId}` - Get quiz statistics
- `GET /api/student-details/{responseId}` - Get detailed student response

### Corrections
- `GET /api/correction/{correctionId}` - Get quiz correction data

## File Structure

```
smart-steps-tutorial/
├── api/                    # API endpoints
│   ├── register.php
│   ├── login.php
│   ├── quiz.php
│   └── ...
├── config/                 # Configuration files
│   └── database.php
├── public/                 # Static files (HTML, CSS, JS)
│   ├── css/
│   ├── js/
│   └── *.html
├── src/                    # PHP classes
│   ├── Auth.php
│   ├── Teacher.php
│   ├── Quiz.php
│   └── Response.php
├── vendor/                 # Composer dependencies
├── .env                    # Environment configuration
├── .htaccess              # Apache URL rewriting
├── composer.json          # PHP dependencies
├── index.php              # Main router
├── install.php            # Installation script
└── README.md
```

## Security Features

- Password hashing with PHP's `password_hash()`
- JWT token authentication
- SQL injection prevention with prepared statements
- XSS protection headers
- CSRF protection for forms
- Input validation and sanitization

## Development

### Adding New Features

1. **Database Changes**: Update `config/database.php` to modify schema
2. **API Endpoints**: Add new files in `api/` directory
3. **Frontend**: Update HTML/CSS/JS files in `public/`
4. **Classes**: Add new PHP classes in `src/` directory

### Testing

- Test all API endpoints with tools like Postman
- Verify database operations and data integrity
- Test responsive design on various devices
- Validate security measures and authentication

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check database credentials in `.env`
   - Ensure MySQL service is running
   - Verify database exists

2. **404 Errors**
   - Check `.htaccess` configuration
   - Ensure URL rewriting is enabled
   - Verify file permissions

3. **Authentication Issues**
   - Check JWT secret in `.env`
   - Clear browser cookies
   - Verify token expiration settings

4. **Composer Dependencies**
   - Run `composer install`
   - Check PHP version compatibility
   - Verify autoloader is working

## License

This project is licensed under the MIT License. See LICENSE file for details.

## Support

For support and questions, please contact the development team or create an issue in the repository.