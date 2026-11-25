# Higoffe — Final Project Summary

## Project Title
**Higoffe: A Café Social Networking and Chat-Based Ordering Platform**

Tagline: *Kape ta, storya ta.*

## 1. Project Concept

Higoffe is a café-focused social networking and ordering application designed for coffee shops, cafés, and small beverage businesses.

The application allows customers to discover cafés, explore menus, view promotions, interact with café posts, and communicate directly with sellers through an integrated messaging system.

Unlike traditional food delivery applications that rely primarily on cart-based ordering, Higoffe focuses on a chat-first experience. Customers can inquire about drinks, ask questions, request customizations, and place orders through conversations.

Sellers can manage incoming inquiries through an organized queue, respond to customers, and convert conversations into structured orders.

The application combines café social networking, customer inquiry management, and chat-based order processing into one platform.

## 2. Target Users

### Customers
- Discover cafés and beverage products
- Follow favorite cafés
- View promotions and café posts
- Chat with sellers
- Inquire about products and customizations
- Place and track orders
- Share experiences and reviews

### Café Sellers
- Create and manage café profiles
- Publish posts and promotions
- Manage menus and product availability
- Receive and organize customer inquiries
- Respond to customers through chat
- Create order proposals inside conversations
- Process and manage customer orders

### Administrator
- Manage users and sellers
- Verify café businesses
- Moderate posts and reported content
- Monitor platform activity
- Manage reports and basic analytics

## 3. Core Features

### A. Authentication and User Roles
- Customer registration and login
- Seller registration and café setup
- Role-based access control
- Admin dashboard
- Seller business verification

### B. Café Social Networking
- Social feed for café posts
- Product and promotional posts
- Likes and comments
- Follow cafés
- Café profiles and menus
- Search and discovery
- Customer reviews and ratings

### C. Customer-Seller Messaging
- Real-time chat
- Conversation history
- Unread message indicators
- Product sharing inside conversations
- Image attachments
- Inquiry status tracking

### D. Seller Inquiry Queue
- Centralized seller inbox
- Waiting, active, and resolved inquiries
- Sellers can accept and handle inquiries
- Queue organized by waiting time
- Inquiry assignment to prevent duplicate handling
- Quick replies and product suggestions

### E. Chat-to-Order System
This is the application's primary differentiating feature.

Customers can discuss their orders naturally through chat. Sellers can create structured order cards containing:

- Product and quantity
- Drink size
- Customizations and add-ons
- Product price
- Additional charges
- Order total
- Pickup or delivery details

Customers can review, confirm, or request changes to the proposed order.

Once confirmed, the order card becomes an official order record in the system.

### F. Order Management
- Seller order dashboard
- Order acceptance and rejection
- Order preparation workflow
- Order status tracking
- Customer order history
- Order cancellation rules
- Notifications for order updates

Suggested order statuses:

Pending → Confirmed → Preparing → Ready for Pickup → Completed

Additional statuses may include Cancelled and Out for Delivery.

### G. Menu Management
- Create and edit products
- Product images and descriptions
- Product prices
- Drink sizes
- Customization options
- Add-ons
- Product availability

### H. Notifications
- New chat messages
- New inquiries
- Inquiry acceptance
- Order confirmation
- Order status updates
- Café announcements

### I. Admin Management
- User management
- Café verification
- Post moderation
- Reported content management
- Basic platform analytics

## 4. Main Customer Workflow

Register/Login → Discover Cafés → View Café Profile → Browse Menu or Social Feed → Start Chat Inquiry → Wait for Seller Response → Discuss Product and Customization → Receive Order Card → Review and Confirm Order → Select Pickup or Delivery → Track Order → Complete Order → Leave Review.

## 5. Main Seller Workflow

Register/Login → Create Café Profile → Add Menu and Products → Publish Café Posts → Receive Customer Inquiry → Inquiry Enters Waiting Queue → Accept Inquiry → Respond Through Chat → Create Order Card → Customer Confirms → Accept Order → Prepare Order → Update Order Status → Complete Transaction.

## 6. Primary Unique Feature

**Chat-to-Order with an Organized Seller Inquiry Queue**

Higoffe is designed around conversations rather than traditional checkout forms.

Customers can naturally ask questions and customize their orders. Sellers manage incoming inquiries through a centralized queue and convert conversations into structured order records.

This helps small cafés organize customer messages, reduce confusion when handling multiple inquiries, and manage orders within one application.

## 7. Initial Project Scope

The MVP should focus on:

- Customer and seller accounts
- Café profiles
- Social feed
- Menu and product management
- Customer-seller chat
- Seller inquiry queue
- Chat-to-order conversion
- Basic order management
- Pickup ordering
- Admin dashboard

Online payment integration, delivery rider management, multi-branch management, advanced recommendation algorithms, and complex loyalty programs should be considered future enhancements unless time permits.

## 8. Development Planning Instructions

Create a comprehensive and technically detailed development plan for Higoffe.

The project will be developed by a four-member BSIS student team as a final project.

The plan must include:

1. Refined project scope and feasibility
2. Functional and non-functional requirements
3. Detailed customer, seller, and administrator workflows
4. System architecture and recommended technology stack
5. Database schema with detailed tables, fields, relationships, and constraints
6. API endpoint design
7. Real-time chat implementation
8. Seller inquiry queue logic and concurrency handling
9. Chat-to-order conversion and order state management
10. UI/UX screen list and navigation structure
11. Authentication, security, and role permissions
12. Task distribution for four team members
13. Development phases and milestones
14. Testing strategy
15. Deployment plan
16. MVP priorities and future enhancements
17. Potential risks and practical solutions

Prioritize a realistic, polished, and fully working customer-to-seller workflow. Avoid unnecessary complexity and do not begin generating code until the complete project plan, architecture, and database design have been established.