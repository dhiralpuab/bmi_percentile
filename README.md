# bmi_percentile
BMI Percentile External Module
This REDCap External Module calculates z-scores and percentiles for Height, Weight, and BMI based on the entered age (in days) and gender. It supports both CDC and WHO growth charts depending on the age range.

**Automatically calculates:**

Height-for-age

Weight-for-age

BMI-for-age

Weight-for-length

Provides both z-scores and percentiles

Updates in real-time when any value changes

Supports pediatric growth standards

**Configuration**
Before using this module, make sure:

All required fields are selected in the configuration screen

All selected fields are present in the same REDCap instrument

**Required Fields:**
Age (in days) – numeric field

Gender – coded as 1 = Male, 2 = Female (must match this coding)

Height (cm) – optional input

Weight (kg) – optional input

BMI (kg/m²) – optional input

**Output Fields (auto-calculated):**
You can choose which output fields to include:

Height-for-age z-score and percentile

Weight-for-age z-score and percentile

BMI-for-age z-score and percentile

**How It Works**
User enters Age (in days) and Gender

When the user enters or updates Height, Weight, or BMI, the corresponding z-scores and percentiles will be auto-calculated and filled

Fields will remain blank if insufficient data is provided

**Notes**

The module uses LMS parameters from the CDC (ages 2–19 years) and WHO (ages 0–60 months) growth standards.

All logic is client-side using JavaScript for speed and responsiveness

Data interpolation is used between LMS values to increase precision

Ensure field names in configuration exactly match the fields in your project

**Installation**

Upload and enable module via the REDCap External Module Manager

Create the required fields in the module's settings OR upload zip file from the github to create instrument.

Configure the EM with your created fields, or upload config zip for uploaded instrument.



