# 🔧 Math Image Upload Fix

## 🚨 **Error Identified**

```
422 (Unprocessable Content)
{
  "message": "The problem text field is required when problem image is not present. (and 1 more error)",
  "errors": {
    "problem_text": ["The problem text field is required when problem image is not present."],
    "problem_image": ["The problem image field is required when problem text is not present."]
  }
}
```

## 🎯 **Problem**

The frontend is trying to send an image but it's not properly formatted as a file upload. The backend expects either:
- `problem_text` (string) OR
- `problem_image` (file upload)

But the frontend is sending neither in the correct format.

## ✅ **Solution**

### **Step 1: Update Math API Client**

**File:** `C:\Users\nazrawi\Documents\development\dymy working\note-gpt-dashboard-main\lib\math-api-client.ts`

**Add image upload method:**

```typescript
// Add this method to your MathApiClient class
async solveMathProblemWithImage(
  imageFile: File, 
  subjectArea: string = 'maths',
  difficultyLevel: string = 'intermediate'
): Promise<MathProblemResponse> {
  const formData = new FormData();
  formData.append('problem_image', imageFile);
  formData.append('subject_area', subjectArea);
  formData.append('difficulty_level', difficultyLevel);

  return this.apiClient.post<MathProblemResponse>('/math/solve', formData, {
    headers: {
      // Don't set Content-Type for FormData - let browser set it
    },
  });
}
```

### **Step 2: Update Math Dashboard Component**

**File:** `C:\Users\nazrawi\Documents\development\dymy working\note-gpt-dashboard-main\components\math\math-dashboard.tsx`

**Add image upload state and handlers:**

```typescript
// Add these to your component state
const [selectedImage, setSelectedImage] = useState<File | null>(null);
const [imagePreview, setImagePreview] = useState<string | null>(null);
const [isImageMode, setIsImageMode] = useState(false);

// Add image upload handler
const handleImageUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
  const file = event.target.files?.[0];
  if (file) {
    // Validate file type
    if (!file.type.startsWith('image/')) {
      showError('Invalid file type', 'Please select an image file.');
      return;
    }

    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
      showError('File too large', 'Please select an image smaller than 10MB.');
      return;
    }

    setSelectedImage(file);
    
    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
      setImagePreview(e.target?.result as string);
    };
    reader.readAsDataURL(file);
  }
};

// Update solve handler to support images
const handleSolve = async () => {
  if (isImageMode && !selectedImage) {
    showError('No image selected', 'Please select an image to solve.');
    return;
  }

  if (!isImageMode && !questionText.trim()) {
    showError('No problem entered', 'Please enter a math problem.');
    return;
  }

  setLoading(true);
  setError(null);
  setSolution(null);

  try {
    let solveResponse;

    if (isImageMode && selectedImage) {
      // Solve image problem
      solveResponse = await mathApi.solveMathProblemWithImage(
        selectedImage,
        'maths',
        'intermediate'
      );
    } else {
      // Solve text problem
      solveResponse = await mathApi.solveMathProblem({
        problem_text: questionText,
        subject_area: 'maths',
        difficulty_level: 'intermediate',
        problem_type: 'text'
      });
    }

    setSolution(solveResponse);
    setQuestionText('');
    setSelectedImage(null);
    setImagePreview(null);
    loadHistory();
  } catch (apiError: any) {
    console.error("API Error:", apiError);
    console.error("Error details:", {
      message: apiError?.message,
      status: apiError?.status,
      response: apiError?.response,
      rawResponse: apiError?.rawResponse
    });
    
    let errorMessage = "Math AI service is temporarily unavailable.";
    
    if (apiError?.message === 'Request was redirected. This usually indicates a network or CORS issue.') {
      errorMessage = "Authentication required. Please log in first.";
    } else if (apiError?.message === 'Failed to fetch') {
      errorMessage = "Backend server is not running. Please start the Laravel backend on port 8000.";
    } else if (apiError?.status === 401) {
      errorMessage = "Authentication required. Please log in first.";
    } else if (apiError?.status === 404) {
      errorMessage = "Math API endpoint not found. Please check if the backend is properly configured.";
    } else if (apiError?.status === 500) {
      errorMessage = "Backend server error. Please check the Laravel logs.";
    } else if (apiError?.userMessage) {
      errorMessage = apiError.userMessage;
    }
    
    showError("Math API Error", errorMessage);
    throw apiError;
  } finally {
    setLoading(false);
  }
};
```

### **Step 3: Update API Client Base Class**

**File:** `C:\Users\nazrawi\Documents\development\dymy working\note-gpt-dashboard-main\lib\api-client.ts`

**Update the request method to handle FormData:**

```typescript
// In the request method, update the config
const config: RequestInit = {
  headers: {
    'Accept': 'application/json',
    'Origin': 'http://localhost:3000',
    // Don't set Content-Type for FormData - let browser set it
    ...(options.body instanceof FormData ? {} : {
      'Content-Type': 'application/json'
    }),
    ...options.headers,
  },
  redirect: 'manual',
  ...options,
};
```

### **Step 4: Update UI with Image Upload**

**Add to your JSX:**

```tsx
{/* Add mode toggle */}
<div className="mb-4">
  <div className="flex space-x-4">
    <button
      onClick={() => setIsImageMode(false)}
      className={`px-4 py-2 rounded ${
        !isImageMode ? 'bg-blue-500 text-white' : 'bg-gray-200'
      }`}
    >
      Text Input
    </button>
    <button
      onClick={() => setIsImageMode(true)}
      className={`px-4 py-2 rounded ${
        isImageMode ? 'bg-blue-500 text-white' : 'bg-gray-200'
      }`}
    >
      Image Upload
    </button>
  </div>
</div>

{/* Text input (existing) */}
{!isImageMode && (
  <div className="mb-4">
    <textarea
      value={questionText}
      onChange={(e) => setQuestionText(e.target.value)}
      placeholder="Enter your math problem here..."
      className="w-full p-3 border rounded-lg"
      rows={4}
    />
  </div>
)}

{/* Image upload */}
{isImageMode && (
  <div className="mb-4">
    <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
      <input
        type="file"
        accept="image/*"
        onChange={handleImageUpload}
        className="hidden"
        id="image-upload"
      />
      <label
        htmlFor="image-upload"
        className="cursor-pointer block"
      >
        {imagePreview ? (
          <div>
            <img
              src={imagePreview}
              alt="Problem preview"
              className="max-w-full max-h-64 mx-auto mb-2 rounded"
            />
            <p className="text-sm text-gray-600">
              Click to change image
            </p>
          </div>
        ) : (
          <div>
            <svg className="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
              <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" />
            </svg>
            <p className="mt-2 text-sm text-gray-600">
              Click to upload an image of your math problem
            </p>
            <p className="text-xs text-gray-500">
              PNG, JPG, GIF up to 10MB
            </p>
          </div>
        )}
      </label>
    </div>
  </div>
)}
```

## 🧪 **Test the Fix**

1. **Add the image upload method** to your MathApiClient
2. **Update the Math Dashboard component** with image upload functionality
3. **Test image upload** - select an image and try to solve
4. **Check backend logs** - should see successful image upload requests

## 🎉 **Expected Results**

After implementing the fix:
- ✅ **Image upload works** - Users can select and upload images
- ✅ **FormData sent correctly** - Backend receives proper file upload
- ✅ **No more 422 errors** - Validation passes
- ✅ **Image processing** - Backend processes images with AI
- ✅ **Solutions returned** - Step-by-step solutions for image problems

The key is using `FormData` for file uploads instead of JSON! 🚀📸



