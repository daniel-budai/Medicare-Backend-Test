# Medicare Code Test

Messaging platform with friend requests and messaging functionality to Medicare

## Installation

1. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

2. **Configure environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Seed test data**:
   ```bash
   php artisan migrate
   php artisan db:seed --class=ChatApiSeeder
   ```
   
   This creates 5 test users with: (password: `password`):
   - alice@example.com
   - bob@example.com
   - charlie@example.com
   - diana@example.com
   - edward@example.com

4. **Start the application**:
   ```bash
   php artisan serve
   ```
   
   Open http://localhost:8000 / http://127.0.0.1:8000/

## Running Tests

```bash
php artisan test
```

### Test Coverage

The project includes **25 feature tests** covering all API endpoints:

```
tests/Feature/Api/
├── RegistrationTest.php      (7 tests)
├── FriendRequestTest.php     (10 tests)
└── MessageTest.php           (8 tests)
```

### Test Commands

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Api/RegistrationTest.php
```

## Email Configuration

For development, the default mail driver is `log`. Emails such as `Verification Email` will be sent to `storage/logs/laravel.log`.


## File Structure

```
app/
├── Enums/
│   └── FriendRequestStatus.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── Auth/
│   │       │   ├── LoginController.php
│   │       │   ├── LogoutController.php
│   │       │   ├── RegisterController.php
│   │       │   ├── ResendVerificationEmailController.php
│   │       │   └── VerifyEmailController.php
│   │       ├── FriendController.php
│   │       ├── MessageController.php
│   │       └── UserController.php
│   ├── Requests/
│   │   ├── Auth/
│   │   │   ├── LoginRequest.php
│   │   │   ├── RegisterRequest.php
│   │   │   └── ResendVerificationRequest.php
│   │   ├── Friend/
│   │   │   └── SendFriendRequestRequest.php
│   │   ├── Message/
│   │   │   ├── ListMessagesRequest.php
│   │   │   └── SendMessageRequest.php
│   │   └── User/
│   │       └── ListUsersRequest.php
│   └── Resources/
│       ├── FriendRequestResource.php
│       ├── FriendshipResource.php
│       ├── MessageResource.php
│       └── UserResource.php
├── Models/
│   ├── FriendRequest.php
│   ├── Friendship.php
│   ├── Message.php
│   └── User.php
├── Policies/
│   └── FriendRequestPolicy.php
└── Services/
    ├── Auth/
    │   └── AuthService.php
    ├── Friend/
    │   └── FriendService.php
    ├── Message/
    │   └── MessageService.php
    └── User/
        └── UserService.php

database/
├── migrations/
│   ├── 2025_10_07_114251_create_friend_requests_table.php
│   ├── 2025_10_07_114257_create_friendships_table.php
│   ├── 2025_10_07_114257_create_messages_table.php
│   └── 2025_10_07_121008_create_personal_access_tokens_table.php
└── seeders/
    └── ChatApiSeeder.php

routes/
└── api.php
```
## Architecture


### Request Flow
```
HTTP Request
    ↓
Middleware (auth, verified)
    ↓
Form Request (validation - auto-runs before controller)
    ↓
Controller (coordinates, calls service)
    ↓
Service (business logic)
    ↓
Model (database operations)
    ↓
Resource (response transformation)
    ↓
JSON Response
```

### Design Patterns

- **Service Layer** - Business logic separated from controllers
- **Form Requests** - Validation rules separated into dedicated classes, keeping controllers thin
- **API Resources** - Consistent JSON response structure across all endpoints
- **Policies** - Authorization logic centralized for friend request actions
- **Enums** - Type-safe status constants (FriendRequestStatus)


### 1. Database 
- `friend_requests` - Track friend requests with status
- `friendships` - Store mutual friend relationships
- `messages` - Store conversations between friends

### 2. Models 
- `FriendRequest` - With sender/receiver relationships
- `Friendship` - Bidirectional friend connections
- `Message` - Chat messages with read tracking
- `User` - Updated with all relationships

### 3. Enum(s)
- `FriendRequestStatus` - Type-safe status values (pending, accepted, rejected, cancelled)

### 4. Services (4)
Business logic is isolated in service classes for clean separation of concerns:

#### AuthService
- `register()` - Register new users and trigger email verification
- `createToken()` - Generate Sanctum API tokens
- `revokeTokens()` - Revoke user tokens on logout

#### UserService
- `getActiveUsers()` - Get paginated list of verified users with filtering
- `findActiveUser()` - Find a specific active user by ID

#### FriendService
- `sendFriendRequest()` - Send friend requests with validation
- `getPendingRequests()` - Get pending requests for a user
- `acceptFriendRequest()` - Accept request and create mutual friendships
- `rejectFriendRequest()` - Reject friend requests
- `getFriends()` - Get user's friends list
- `areFriends()` - Check if two users are friends

#### MessageService
- `sendMessage()` - Send messages between friends
- `getMessagesBetweenUsers()` - Get paginated conversation history

### 5. Form Requests
All endpoints have dedicated validation classes:
- `RegisterRequest` - User registration validation
- `LoginRequest` - Login credentials validation
- `ResendVerificationRequest` - Resend verification email validation
- `SendFriendRequestRequest` - Friend request validation
- `ListUsersRequest` - Pagination/filtering validation
- `SendMessageRequest` - Message validation
- `ListMessagesRequest` - Message pagination validation

### 6. API Resources
Transform database models into consistent API responses:
- `UserResource` - User data transformation
- `FriendRequestResource` - Friend request responses
- `FriendshipResource` - Friend list responses
- `MessageResource` - Message responses

### 7. Controllers
Lightweight HTTP layer handling requests and responses:

#### Auth Controllers 
- `RegisterController` - User registration
- `LoginController` - Authentication
- `LogoutController` - Token revocation
- `VerifyEmailController` - Email verification
- `ResendVerificationEmailController` - Resend verification email

#### Feature Controllers 
- `UserController` - List active users
- `FriendController` - Friend management (send/accept/reject requests, list friends)
- `MessageController` - Messaging (send messages, get conversation history)

### 8. Policies
- `FriendRequestPolicy` - Authorization rules for friend request actions

### 9. Routes
- Complete API route structure in `routes/api.php`
- Public routes: registration, login, email verification
- Protected routes: require authentication via Sanctum
- Verified routes: require email verification for sensitive features

## API Endpoints

Base URL: `http://localhost:8000/api`

### Authentication

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `POST` | `/auth/register` | Register new user | No |
| `POST` | `/auth/login` | Login and get token | No |
| `POST` | `/auth/logout` | Revoke token | Yes |
| `POST` | `/email/resend-verification` | Resend verification email | No |
| `GET` | `/email/verify/{id}/{hash}` | Verify email address | No (signed) |

### Users

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `GET` | `/users` | List active verified users | Yes (verified) |

**Query Parameters:** `per_page`, `page`, `name`, `email`

### Friends

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `POST` | `/friends/request` | Send friend request | Yes (verified) |
| `GET` | `/friends/requests` | Get pending requests | Yes (verified) |
| `POST` | `/friends/requests/{id}/accept` | Accept friend request | Yes (verified) |
| `POST` | `/friends/requests/{id}/reject` | Reject friend request | Yes (verified) |
| `GET` | `/friends` | Get friends list | Yes (verified) |

### Messages

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `POST` | `/messages` | Send message to friend | Yes (verified) |
| `GET` | `/messages/{friendId}` | Get conversation history | Yes (verified) |

**Query Parameters:** `per_page`, `page`

**Total: 13 endpoints**

---

### Testing with Postman

Import `Chat API.postman_collection.json` into Postman for testing. 

- Auto-saves auth token after login
- Includes all endpoints with example requests
- Pre-configured with base URL

---

