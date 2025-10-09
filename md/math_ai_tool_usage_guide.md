# 🧮 **AI Math Tool - Complete Usage Guide**

## 📋 **Overview**

The AI Math Tool allows you to solve mathematical problems using both text input and image uploads. It provides step-by-step solutions, explanations, and maintains a history of all your math problems.

---

## 🚀 **Getting Started**

### **Step 1: Authentication**
Before using the Math AI tool, ensure you have a valid authentication token:

1. **Login to your account** through the frontend
2. **Get your Bearer token** from the authentication system
3. **Include the token** in all API requests:
   ```
   Authorization: Bearer {your_token}
   ```

### **Step 2: Verify Subscription**
Ensure you have an active subscription to use the Math AI tool:
- The tool requires an active subscription
- Check your subscription status in your account settings

---

## 📝 **Text-Based Math Problems**

### **Step 1: Prepare Your Problem**
Write your mathematical problem clearly:
- **Examples:**
  - "What is 2 + 2?"
  - "Solve for x: 2x + 5 = 15"
  - "Find the derivative of x² + 3x + 2"
  - "Calculate the area of a circle with radius 5"

### **Step 2: Choose Subject Area**
Select the appropriate subject area:
- `arithmetic` - Basic math operations
- `algebra` - Algebraic equations and expressions
- `geometry` - Shapes, areas, volumes
- `calculus` - Derivatives, integrals
- `statistics` - Data analysis, probability
- `trigonometry` - Angles, triangles, functions
- `maths` - General mathematics

### **Step 3: Set Difficulty Level**
Choose the difficulty level:
- `beginner` - Basic problems
- `intermediate` - Moderate complexity (default)
- `advanced` - Complex problems

### **Step 4: Send the Request**
Make a POST request to `/math/solve`:

**Request Format:**
```json
{
  "problem_text": "What is 2 + 2?",
  "subject_area": "arithmetic",
  "difficulty_level": "beginner"
}
```

**Required Headers:**
```
Authorization: Bearer {your_token}
Content-Type: application/json
Accept: application/json
```

### **Step 5: Review the Solution**
The response will include:
- **Step-by-step solution** - Detailed solving process
- **Final answer** - The correct result
- **Explanation** - Why the solution works
- **Verification** - How to check the answer
- **Solution method** - The approach used

---

## 🖼️ **Image-Based Math Problems**

### **Step 1: Prepare Your Image**
Ensure your image meets the requirements:
- **Supported formats:** JPEG, PNG, GIF, WebP
- **Maximum size:** 10MB
- **Image quality:** Clear, readable text and equations
- **Content:** Mathematical problems, equations, or diagrams

### **Step 2: Take a Clear Photo**
For best results:
- **Good lighting** - Ensure the image is well-lit
- **Sharp focus** - Avoid blurry images
- **Complete problem** - Include the entire problem
- **Readable text** - Make sure numbers and symbols are clear

### **Step 3: Upload the Image**
Use FormData to upload your image:

**Request Format:**
```
Content-Type: multipart/form-data

problem_image: [your_image_file]
subject_area: "maths"
difficulty_level: "intermediate"
```

**Required Headers:**
```
Authorization: Bearer {your_token}
Accept: application/json
```

### **Step 4: Process the Solution**
The AI will:
1. **Analyze the image** - Extract the mathematical problem
2. **Identify the problem type** - Determine the subject area
3. **Solve step-by-step** - Provide detailed solution
4. **Return results** - Same format as text problems

---

## 📚 **Viewing Math History**

### **Step 1: Get Your History**
Request your math problem history:

**Endpoint:** `GET /math/history`

**Query Parameters (Optional):**
- `per_page` - Number of results per page (default: 15)
- `subject` - Filter by subject area
- `difficulty` - Filter by difficulty level

**Example:** `GET /math/history?per_page=10&subject=algebra`

### **Step 2: Review Past Problems**
The history shows:
- **Problem text** or **image reference**
- **Subject area** and **difficulty level**
- **Problem type** (text or image)
- **Date created**
- **Problem ID** for further reference

### **Step 3: Access Specific Problems**
Use the problem ID to get detailed information:

**Endpoint:** `GET /math/problems/{id}`

This returns:
- **Complete problem details**
- **All solutions** associated with the problem
- **Step-by-step solutions**
- **Explanations and verifications**

---

## 📊 **Math Statistics**

### **Step 1: Get Your Stats**
Request your math statistics:

**Endpoint:** `GET /math/stats`

### **Step 2: Review Your Progress**
Statistics include:
- **Total problems solved**
- **Problems by subject area**
- **Problems by difficulty level**
- **Recent activity** (problems per day)
- **Success rate** percentage

---

## 🗂️ **Managing Math Problems**

### **View All Problems (Paginated)**
Get a paginated list of all your problems:

**Endpoint:** `GET /math/problems`

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Results per page (default: 15)
- `subject` - Filter by subject area
- `difficulty` - Filter by difficulty level

### **Delete Problems**
Remove problems you no longer need:

**Endpoint:** `DELETE /math/problems/{id}`

**Note:** This will also delete associated files and solutions.

---

## 🔧 **Advanced Usage**

### **Filtering and Search**
Use query parameters to filter results:

**Examples:**
```
GET /math/history?subject=algebra&difficulty=advanced
GET /math/problems?page=2&per_page=20
GET /math/history?per_page=50
```

### **Batch Operations**
For multiple problems:
1. **Get problem IDs** from history or problems list
2. **Process each problem** individually
3. **Use pagination** for large datasets

---

## 🚨 **Troubleshooting**

### **Common Issues:**

#### **Authentication Errors (401)**
- **Problem:** Invalid or expired token
- **Solution:** Re-authenticate and get a new token

#### **Subscription Errors (403)**
- **Problem:** No active subscription
- **Solution:** Check subscription status and renew if needed

#### **Validation Errors (422)**
- **Problem:** Missing required fields
- **Solution:** Ensure all required fields are provided

#### **Image Upload Issues**
- **Problem:** Image not processing
- **Solution:** Check file format, size, and quality

#### **Server Errors (500)**
- **Problem:** Backend processing error
- **Solution:** Try again or contact support

---

## 📱 **Best Practices**

### **For Text Problems:**
1. **Be specific** - Include all necessary information
2. **Use clear language** - Avoid ambiguous terms
3. **Include context** - Provide relevant background
4. **Check spelling** - Ensure mathematical terms are correct

### **For Image Problems:**
1. **High quality images** - Use good lighting and focus
2. **Complete problems** - Include the entire question
3. **Readable text** - Ensure numbers and symbols are clear
4. **Appropriate format** - Use supported image formats

### **For History Management:**
1. **Regular cleanup** - Delete old problems you don't need
2. **Use filters** - Find specific problems quickly
3. **Monitor progress** - Check statistics regularly
4. **Backup important solutions** - Save critical results

---

## 🎯 **Quick Reference**

### **Essential Endpoints:**
| **Action** | **Method** | **Endpoint** |
|------------|------------|--------------|
| Solve text problem | POST | `/math/solve` |
| Solve image problem | POST | `/math/solve` |
| Get history | GET | `/math/history` |
| Get problems | GET | `/math/problems` |
| Get specific problem | GET | `/math/problems/{id}` |
| Delete problem | DELETE | `/math/problems/{id}` |
| Get statistics | GET | `/math/stats` |

### **Required Headers:**
```
Authorization: Bearer {your_token}
Accept: application/json
```

### **Content Types:**
- **Text problems:** `application/json`
- **Image problems:** `multipart/form-data`

---

## ✅ **Success Tips**

1. **Start simple** - Begin with basic problems to understand the tool
2. **Use appropriate subject areas** - Match the problem type
3. **Check your work** - Verify solutions using the verification section
4. **Learn from explanations** - Understand the solution methods
5. **Track your progress** - Use statistics to monitor improvement
6. **Organize your history** - Use filters and pagination effectively

---

## 🚀 **Ready to Use**

The AI Math Tool is now ready for use! Follow these steps to get started with solving mathematical problems, whether through text input or image uploads. The tool will provide detailed, step-by-step solutions to help you understand and learn mathematics.
