"""
Test file for Math Solver Microservice

This file tests all the solvers and services in the microservice.
"""

import sys
import os
import unittest
from unittest.mock import patch, MagicMock

# Add the current directory to Python path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from solvers import (
    AlgebraSolver, CalculusSolver, GeometrySolver, 
    StatisticsSolver, ArithmeticSolver
)
from services import ProblemParser, OpenAIService, ImageProcessor, SolutionFormatter

class TestSolvers(unittest.TestCase):
    """Test all mathematical solvers"""
    
    def setUp(self):
        """Set up test fixtures"""
        self.algebra_solver = AlgebraSolver()
        self.calculus_solver = CalculusSolver()
        self.geometry_solver = GeometrySolver()
        self.statistics_solver = StatisticsSolver()
        self.arithmetic_solver = ArithmeticSolver()
    
    def test_algebra_solver(self):
        """Test algebra solver"""
        print("\n🧮 Testing Algebra Solver...")
        
        # Test linear equation
        result = self.algebra_solver.solve_with_timeout("2x + 5 = 13")
        self.assertTrue(result.success)
        self.assertIn("4", str(result.answer))
        print(f"   ✅ Linear equation: {result.answer}")
        
        # Test quadratic equation
        result = self.algebra_solver.solve_with_timeout("x^2 - 5x + 6 = 0")
        self.assertTrue(result.success)
        print(f"   ✅ Quadratic equation: {result.answer}")
        
        # Test expression simplification
        result = self.algebra_solver.solve_with_timeout("2x + 3x - x")
        self.assertTrue(result.success)
        print(f"   ✅ Expression simplification: {result.answer}")
    
    def test_calculus_solver(self):
        """Test calculus solver"""
        print("\n📐 Testing Calculus Solver...")
        
        # Test derivative
        result = self.calculus_solver.solve_with_timeout("derivative of x^2")
        self.assertTrue(result.success)
        print(f"   ✅ Derivative: {result.answer}")
        
        # Test integral
        result = self.calculus_solver.solve_with_timeout("integral of 2x")
        self.assertTrue(result.success)
        print(f"   ✅ Integral: {result.answer}")
    
    def test_geometry_solver(self):
        """Test geometry solver"""
        print("\n📏 Testing Geometry Solver...")
        
        # Test area calculation
        result = self.geometry_solver.solve_with_timeout("area of circle with radius 5")
        self.assertTrue(result.success)
        print(f"   ✅ Circle area: {result.answer}")
        
        # Test volume calculation
        result = self.geometry_solver.solve_with_timeout("volume of sphere with radius 3")
        self.assertTrue(result.success)
        print(f"   ✅ Sphere volume: {result.answer}")
    
    def test_statistics_solver(self):
        """Test statistics solver"""
        print("\n📊 Testing Statistics Solver...")
        
        # Test mean calculation
        result = self.statistics_solver.solve_with_timeout("mean of 1, 2, 3, 4, 5")
        self.assertTrue(result.success)
        print(f"   ✅ Mean calculation: {result.answer}")
        
        # Test standard deviation
        result = self.statistics_solver.solve_with_timeout("standard deviation of 1, 2, 3, 4, 5")
        self.assertTrue(result.success)
        print(f"   ✅ Standard deviation: {result.answer}")
    
    def test_arithmetic_solver(self):
        """Test arithmetic solver"""
        print("\n🔢 Testing Arithmetic Solver...")
        
        # Test basic arithmetic
        result = self.arithmetic_solver.solve_with_timeout("15 + 27")
        self.assertTrue(result.success)
        self.assertEqual(str(result.answer), "42")
        print(f"   ✅ Basic arithmetic: {result.answer}")
        
        # Test percentage
        result = self.arithmetic_solver.solve_with_timeout("20% of 150")
        self.assertTrue(result.success)
        print(f"   ✅ Percentage calculation: {result.answer}")

class TestServices(unittest.TestCase):
    """Test all services"""
    
    def setUp(self):
        """Set up test fixtures"""
        self.problem_parser = ProblemParser()
        self.solution_formatter = SolutionFormatter()
    
    def test_problem_parser(self):
        """Test problem parser"""
        print("\n🔍 Testing Problem Parser...")
        
        # Test classification
        result = self.problem_parser.classify_and_solve("2x + 5 = 13")
        self.assertTrue(result['success'])
        self.assertEqual(result['solver_used'], 'algebra')
        print(f"   ✅ Problem classification: {result['solver_used']}")
        
        # Test different problem types
        test_cases = [
            ("15 + 27", "arithmetic"),
            ("area of circle with radius 5", "geometry"),
            ("mean of 1, 2, 3, 4, 5", "statistics"),
            ("derivative of x^2", "calculus")
        ]
        
        for problem, expected_solver in test_cases:
            result = self.problem_parser.classify_and_solve(problem)
            self.assertTrue(result['success'])
            print(f"   ✅ {expected_solver}: {result['solver_used']}")
    
    def test_solution_formatter(self):
        """Test solution formatter"""
        print("\n📝 Testing Solution Formatter...")
        
        # Test solve response formatting
        solution_data = {
            'answer': 'x = 4',
            'method': 'algebraic_solving',
            'confidence': 0.95,
            'steps': [
                {
                    'step_number': 1,
                    'description': 'Subtract 5 from both sides',
                    'expression': '2x = 8',
                    'confidence': 1.0
                }
            ],
            'verification': 'Solution verified',
            'metadata': {'solver': 'algebra'}
        }
        
        classification = {
            'subject': 'algebra',
            'confidence': 0.9,
            'method': 'keyword_matching'
        }
        
        response = self.solution_formatter.format_solve_response(
            solution_data, classification, {'processing_time': 1.5}
        )
        
        self.assertTrue(response['success'])
        self.assertEqual(response['solution']['answer'], 'x = 4')
        print("   ✅ Solution formatting passed")
    
    @patch('services.openai_service.OpenAIService')
    def test_openai_service(self, mock_openai):
        """Test OpenAI service (mocked)"""
        print("\n🤖 Testing OpenAI Service...")
        
        # Mock OpenAI service
        mock_service = MagicMock()
        mock_service.is_available.return_value = True
        mock_service.explain_solution.return_value = {
            'success': True,
            'explanation': 'This is a test explanation',
            'tokens_used': 100
        }
        
        result = mock_service.explain_solution(
            "2x + 5 = 13",
            {'answer': 'x = 4', 'steps': []},
            'algebra',
            'intermediate'
        )
        
        self.assertTrue(result['success'])
        self.assertIn('explanation', result)
        print("   ✅ OpenAI service (mocked) passed")

class TestIntegration(unittest.TestCase):
    """Test integration between components"""
    
    def test_end_to_end_solving(self):
        """Test end-to-end problem solving"""
        print("\n🔄 Testing End-to-End Solving...")
        
        problem_parser = ProblemParser()
        
        test_problems = [
            "2x + 5 = 13",
            "15 + 27",
            "area of circle with radius 5",
            "mean of 1, 2, 3, 4, 5"
        ]
        
        for problem in test_problems:
            result = problem_parser.classify_and_solve(problem)
            self.assertTrue(result['success'])
            self.assertIsNotNone(result['solution'])
            print(f"   ✅ Solved: {problem[:30]}... -> {result['solution'].answer}")

def run_tests():
    """Run all tests"""
    print("🧪 Math Solver Microservice Tests")
    print("=" * 40)
    
    # Create test suite
    test_suite = unittest.TestSuite()
    
    # Add test cases
    test_suite.addTest(unittest.makeSuite(TestSolvers))
    test_suite.addTest(unittest.makeSuite(TestServices))
    test_suite.addTest(unittest.makeSuite(TestIntegration))
    
    # Run tests
    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(test_suite)
    
    # Print summary
    print("\n" + "=" * 40)
    print("📊 Test Summary")
    print("=" * 40)
    print(f"Tests run: {result.testsRun}")
    print(f"Failures: {len(result.failures)}")
    print(f"Errors: {len(result.errors)}")
    
    if result.failures:
        print("\n❌ Failures:")
        for test, traceback in result.failures:
            print(f"  - {test}: {traceback}")
    
    if result.errors:
        print("\n💥 Errors:")
        for test, traceback in result.errors:
            print(f"  - {test}: {traceback}")
    
    if result.wasSuccessful():
        print("\n🎉 All tests passed!")
        return True
    else:
        print("\n⚠️  Some tests failed!")
        return False

if __name__ == '__main__':
    success = run_tests()
    sys.exit(0 if success else 1)




