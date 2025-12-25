#!/bin/bash

# Script to create GitHub issues for Shopping Complex project
# Make sure you're authenticated with: gh auth login

echo "Creating GitHub issues for Shopping Complex..."
echo "=============================================="

# Phase 1: Foundation & Authentication - Backend

echo "Creating Issue #1: Implement Repository Pattern Structure"
gh issue create \
  --title "Implement Repository Pattern Structure" \
  --label "backend,architecture,good-first-issue" \
  --body "## Description
Set up the Repository pattern structure for the project to separate data access logic from business logic.

## Tasks
- [ ] Create \`app/Repositories/Contracts\` directory
- [ ] Create base \`RepositoryInterface\` with common CRUD methods
- [ ] Create \`app/Repositories/Eloquent\` directory
- [ ] Create base \`EloquentRepository\` class implementing the interface
- [ ] Update \`AppServiceProvider\` to bind repositories
- [ ] Add documentation comments

## Acceptance Criteria
- Repository structure follows Laravel best practices
- Base interfaces and classes are reusable
- Service provider properly binds interfaces to implementations
- Code is well-documented

## Difficulty
⭐⭐ (4-6 hours)

## Files to Create
- \`app/Repositories/Contracts/RepositoryInterface.php\`
- \`app/Repositories/Eloquent/EloquentRepository.php\`

See [development.md](development.md) for architectural guidelines."

echo "Creating Issue #2: Create User Repository"
gh issue create \
  --title "Create User Repository" \
  --label "backend,repository,authentication" \
  --body "## Description
Implement UserRepository following the Repository pattern established in Issue #1.

## Dependencies
Requires #1 to be completed first.

## Tasks
- [ ] Create \`UserRepositoryInterface\` with user-specific methods
- [ ] Implement \`UserRepository\` extending \`EloquentRepository\`
- [ ] Add methods: \`findByEmail\`, \`findByRole\`, \`updateRole\`, \`verifyEmail\`
- [ ] Write unit tests for all repository methods
- [ ] Bind interface in service provider

## Acceptance Criteria
- All CRUD operations work correctly
- Custom methods handle edge cases
- Unit tests achieve >80% coverage
- Repository is properly bound in container

## Difficulty
⭐⭐ (3-4 hours)

## Files to Create
- \`app/Repositories/Contracts/UserRepositoryInterface.php\`
- \`app/Repositories/Eloquent/UserRepository.php\`
- \`tests/Unit/Repositories/UserRepositoryTest.php\`"

echo "Creating Issue #3: Implement Social Authentication (Google)"
gh issue create \
  --title "Implement Social Authentication (Google)" \
  --label "backend,authentication,integration" \
  --body "## Description
Add Google OAuth authentication using Laravel Socialite.

## Tasks
- [ ] Install Laravel Socialite package
- [ ] Configure Google OAuth credentials
- [ ] Create \`SocialAuthController\` with redirect and callback methods
- [ ] Update User model to handle social login
- [ ] Create/update user on successful authentication
- [ ] Add routes for social auth
- [ ] Write feature tests

## Acceptance Criteria
- Users can log in with Google account
- New users are created automatically
- Existing users can link Google account
- Proper error handling for failed authentication
- Tests cover happy path and error scenarios

## Difficulty
⭐⭐⭐ (6-8 hours)

## Files to Create
- \`app/Http/Controllers/Auth/SocialAuthController.php\`
- \`tests/Feature/Auth/SocialAuthTest.php\`

## Files to Modify
- \`routes/web.php\`
- \`config/services.php\`"

echo "Creating Issue #4: Email Verification System"
gh issue create \
  --title "Email Verification System" \
  --label "backend,authentication,email" \
  --body "## Description
Implement email verification for new user registrations.

## Tasks
- [ ] Update User model to implement \`MustVerifyEmail\`
- [ ] Create custom email verification notification
- [ ] Add verification routes and middleware
- [ ] Create email template
- [ ] Add resend verification email endpoint
- [ ] Write tests for verification flow

## Acceptance Criteria
- Users receive verification email on registration
- Email contains valid verification link
- Users can resend verification email
- Unverified users are restricted from certain actions
- Tests cover the complete verification flow

## Difficulty
⭐⭐ (4-5 hours)

## Files to Create
- \`app/Notifications/CustomVerifyEmail.php\`
- \`resources/views/emails/verify-email.blade.php\`
- \`tests/Feature/Auth/EmailVerificationTest.php\`"

echo "Creating Issue #5: Password Reset Functionality"
gh issue create \
  --title "Password Reset Functionality" \
  --label "backend,authentication,email" \
  --body "## Description
Implement secure password reset functionality with email notifications.

## Tasks
- [ ] Create \`ForgotPasswordController\`
- [ ] Create \`ResetPasswordController\`
- [ ] Configure password reset email template
- [ ] Add rate limiting for reset requests
- [ ] Implement token expiration (1 hour)
- [ ] Add validation for password strength
- [ ] Write comprehensive tests

## Acceptance Criteria
- Users can request password reset via email
- Reset tokens expire after 1 hour
- Rate limiting prevents abuse
- New password meets strength requirements
- Old tokens are invalidated after use
- Tests cover edge cases

## Difficulty
⭐⭐ (5-6 hours)

## Files to Create
- \`app/Http/Controllers/Auth/ForgotPasswordController.php\`
- \`app/Http/Controllers/Auth/ResetPasswordController.php\`
- \`tests/Feature/Auth/PasswordResetTest.php\`"

# Phase 1: Frontend

echo "Creating Issue #6: Set Up React + TypeScript Structure"
gh issue create \
  --title "Set Up React + TypeScript Structure" \
  --label "frontend,setup,good-first-issue" \
  --body "## Description
Organize React + TypeScript structure with proper typing and component organization.

## Tasks
- [ ] Create \`resources/ts/types\` directory for TypeScript types
- [ ] Define user types (User, Customer, Vendor, Admin)
- [ ] Create \`resources/ts/components\` directory structure
- [ ] Set up base layout component
- [ ] Configure TypeScript strict mode
- [ ] Add path aliases in tsconfig.json

## Acceptance Criteria
- TypeScript configuration is optimal
- Clear directory structure for components
- All types are properly defined
- No TypeScript errors
- Path aliases work correctly

## Difficulty
⭐⭐ (3-4 hours)

## Files to Create
- \`resources/ts/types/user.ts\`
- \`resources/ts/types/product.ts\`
- \`resources/ts/components/Layout/BaseLayout.tsx\`

## Files to Modify
- \`tsconfig.json\`"

echo "Creating Issue #7: Create Authentication Pages"
gh issue create \
  --title "Create Authentication Pages" \
  --label "frontend,authentication,UI" \
  --body "## Description
Build complete authentication UI with Login, Register, and Password Reset pages.

## Tasks
- [ ] Create Login page component
- [ ] Create Register page component
- [ ] Create Forgot Password page component
- [ ] Create Reset Password page component
- [ ] Add form validation with proper error messages
- [ ] Implement social login buttons (Google)
- [ ] Add loading states
- [ ] Make responsive for mobile
- [ ] Add animations and transitions

## Acceptance Criteria
- All forms have client-side validation
- Error messages are user-friendly
- Forms are accessible (ARIA labels)
- Pages are fully responsive
- Social login buttons trigger OAuth flow
- Loading states provide feedback

## Difficulty
⭐⭐⭐ (8-10 hours)

## Files to Create
- \`resources/ts/pages/auth/Login.tsx\`
- \`resources/ts/pages/auth/Register.tsx\`
- \`resources/ts/pages/auth/ForgotPassword.tsx\`
- \`resources/ts/pages/auth/ResetPassword.tsx\`
- \`resources/ts/components/Forms/Input.tsx\`
- \`resources/ts/components/Forms/Button.tsx\`"

echo "Creating Issue #8: Implement Role-Based Routing"
gh issue create \
  --title "Implement Role-Based Routing" \
  --label "frontend,routing,authorization" \
  --body "## Description
Create route protection based on user roles (Customer, Vendor, Admin).

## Tasks
- [ ] Create \`ProtectedRoute\` component
- [ ] Create role checker utility
- [ ] Implement redirect logic for unauthorized access
- [ ] Add role-specific layouts
- [ ] Create route configuration
- [ ] Handle authentication state

## Acceptance Criteria
- Users are redirected based on their role
- Unauthenticated users are sent to login
- Role checks happen on route change
- Proper error messages for unauthorized access
- No flash of unauthorized content

## Difficulty
⭐⭐⭐ (4-5 hours)

## Files to Create
- \`resources/ts/components/Routes/ProtectedRoute.tsx\`
- \`resources/ts/utils/roleChecker.ts\`
- \`resources/ts/config/routes.ts\`"

# Phase 2: Vendor Management - Backend

echo "Creating Issue #9: Create Product Repository and Service"
gh issue create \
  --title "Create Product Repository and Service" \
  --label "backend,repository,service-layer" \
  --body "## Description
Implement ProductRepository and ProductService with full CRUD operations.

## Dependencies
Requires #1 (Repository Pattern) to be completed first.

## Tasks
- [ ] Create \`ProductRepositoryInterface\`
- [ ] Implement \`ProductRepository\`
- [ ] Create \`ProductService\` for business logic
- [ ] Add methods: \`getByVendor\`, \`getActive\`, \`search\`, \`updateStock\`
- [ ] Implement soft deletes
- [ ] Add eager loading for relationships
- [ ] Write unit and feature tests

## Acceptance Criteria
- Repository handles all product data access
- Service layer contains business logic
- Relationships are properly eager-loaded
- Soft deletes work correctly
- Tests cover all methods

## Difficulty
⭐⭐⭐ (6-8 hours)

## Files to Create
- \`app/Repositories/Contracts/ProductRepositoryInterface.php\`
- \`app/Repositories/Eloquent/ProductRepository.php\`
- \`app/Services/ProductService.php\`
- \`tests/Unit/Services/ProductServiceTest.php\`"

echo "Creating Issue #10: Product Management API Endpoints"
gh issue create \
  --title "Product Management API Endpoints" \
  --label "backend,API,REST" \
  --body "## Description
Create RESTful API endpoints for product management (vendor side).

## Dependencies
Requires #9 (Product Repository and Service) to be completed first.

## Tasks
- [ ] Create \`ProductController\` (API)
- [ ] Implement CRUD endpoints
- [ ] Create \`ProductRequest\` for validation
- [ ] Create \`ProductResource\` for API responses
- [ ] Add pagination for product lists
- [ ] Implement authorization policies
- [ ] Add API documentation comments
- [ ] Write API tests

## Acceptance Criteria
- All endpoints follow REST conventions
- Proper HTTP status codes
- Validation prevents invalid data
- Authorization ensures vendors only manage their products
- Responses use resource transformers
- Comprehensive API tests

## Difficulty
⭐⭐⭐ (8-10 hours)

## API Endpoints
\`\`\`
GET    /api/products
POST   /api/products
GET    /api/products/{id}
PUT    /api/products/{id}
DELETE /api/products/{id}
\`\`\`

## Files to Create
- \`app/Http/Controllers/API/ProductController.php\`
- \`app/Http/Requests/ProductRequest.php\`
- \`app/Http/Resources/ProductResource.php\`
- \`app/Policies/ProductPolicy.php\`
- \`tests/Feature/API/ProductControllerTest.php\`"

echo "Creating Issue #11: Image Upload for Products"
gh issue create \
  --title "Image Upload for Products" \
  --label "backend,media,storage" \
  --body "## Description
Implement secure image upload system for product images with validation and optimization.

## Tasks
- [ ] Create \`MediaService\` for file handling
- [ ] Add image validation (type, size, dimensions)
- [ ] Implement image optimization (resize, compress)
- [ ] Store images in storage/products
- [ ] Create database records in \`media\` table
- [ ] Add multiple images per product support
- [ ] Implement image deletion
- [ ] Write tests for upload scenarios

## Acceptance Criteria
- Only valid image types accepted (jpg, png, webp)
- Images are optimized before storage
- Max file size enforced (5MB)
- Multiple images per product supported
- Old images deleted when replaced
- Tests cover validation and storage

## Difficulty
⭐⭐⭐ (6-8 hours)

## Files to Create
- \`app/Services/MediaService.php\`
- \`app/Http/Controllers/API/MediaController.php\`
- \`tests/Feature/MediaUploadTest.php\`"

echo "Creating Issue #12: Vendor Dashboard Analytics API"
gh issue create \
  --title "Vendor Dashboard Analytics API" \
  --label "backend,API,analytics" \
  --body "## Description
Create API endpoints for vendor dashboard analytics (sales, orders, revenue).

## Tasks
- [ ] Create \`AnalyticsService\`
- [ ] Calculate total revenue (daily, weekly, monthly)
- [ ] Count total orders by status
- [ ] Get top-selling products
- [ ] Calculate average order value
- [ ] Create analytics API endpoints
- [ ] Add date range filtering
- [ ] Optimize queries for performance
- [ ] Write tests

## Acceptance Criteria
- Analytics data is accurate
- Queries are optimized (no N+1)
- Date range filtering works correctly
- Response time < 500ms for typical data
- Tests verify calculations

## Difficulty
⭐⭐⭐⭐ (10-12 hours)

## Files to Create
- \`app/Services/AnalyticsService.php\`
- \`app/Http/Controllers/API/AnalyticsController.php\`
- \`app/Http/Resources/AnalyticsResource.php\`
- \`tests/Feature/AnalyticsTest.php\`"

# Phase 2: Vendor Management - Frontend

echo "Creating Issue #13: Vendor Onboarding Flow"
gh issue create \
  --title "Vendor Onboarding Flow" \
  --label "frontend,UI/UX,forms" \
  --body "## Description
Create multi-step vendor onboarding flow with business information collection.

## Tasks
- [ ] Design multi-step form (3-4 steps)
- [ ] Step 1: Business Information
- [ ] Step 2: Business Verification (documents)
- [ ] Step 3: Bank/Payment Details
- [ ] Step 4: Review & Submit
- [ ] Add progress indicator
- [ ] Save progress (draft state)
- [ ] Form validation per step
- [ ] Mobile responsive design

## Acceptance Criteria
- Users can navigate between steps
- Progress is saved automatically
- All fields have validation
- File uploads work correctly
- Form submission creates vendor profile
- Mobile experience is smooth

## Difficulty
⭐⭐⭐⭐ (12-15 hours)

## Files to Create
- \`resources/ts/pages/vendor/Onboarding.tsx\`
- \`resources/ts/components/Vendor/OnboardingSteps/\`
- \`resources/ts/hooks/useOnboarding.ts\`"

echo "Creating Issue #14: Product Management Dashboard"
gh issue create \
  --title "Product Management Dashboard" \
  --label "frontend,dashboard,CRUD" \
  --body "## Description
Build comprehensive product management interface for vendors.

## Dependencies
Requires #10 (Product Management API) to be completed first.

## Tasks
- [ ] Create product list view with search/filter
- [ ] Add product form (create/edit)
- [ ] Implement image upload with preview
- [ ] Add inventory management
- [ ] Create product status toggle (active/inactive)
- [ ] Add bulk actions (delete, activate, deactivate)
- [ ] Implement pagination
- [ ] Add loading and error states
- [ ] Make fully responsive

## Acceptance Criteria
- Products display in data table
- Search and filters work smoothly
- Image upload shows preview
- Form validation prevents errors
- Bulk actions work correctly
- UI is intuitive and fast
- Mobile-friendly

## Difficulty
⭐⭐⭐⭐ (15-20 hours)

## Files to Create
- \`resources/ts/pages/vendor/Products.tsx\`
- \`resources/ts/components/Vendor/ProductList.tsx\`
- \`resources/ts/components/Vendor/ProductForm.tsx\`
- \`resources/ts/components/Shared/ImageUpload.tsx\`"

echo "Creating Issue #15: Vendor Analytics Dashboard"
gh issue create \
  --title "Vendor Analytics Dashboard" \
  --label "frontend,dashboard,charts" \
  --body "## Description
Create visual analytics dashboard for vendors with charts and statistics.

## Dependencies
Requires #12 (Vendor Dashboard Analytics API) to be completed first.

## Tasks
- [ ] Install charting library (Chart.js or Recharts)
- [ ] Create revenue chart (line/bar)
- [ ] Display order statistics cards
- [ ] Show top-selling products
- [ ] Add date range picker
- [ ] Implement data fetching and caching
- [ ] Add loading skeletons
- [ ] Make responsive

## Acceptance Criteria
- Charts display accurate data
- Date range filter updates all widgets
- Dashboard loads quickly
- Data updates without full page refresh
- Mobile view stacks widgets nicely

## Difficulty
⭐⭐⭐⭐ (12-15 hours)

## Files to Create
- \`resources/ts/pages/vendor/Dashboard.tsx\`
- \`resources/ts/components/Charts/RevenueChart.tsx\`
- \`resources/ts/components/Vendor/StatCard.tsx\`
- \`resources/ts/hooks/useAnalytics.ts\`"

echo ""
echo "✅ Created 15 GitHub issues successfully!"
echo ""
echo "To create more issues (16-40), run: ./create-more-issues.sh"
