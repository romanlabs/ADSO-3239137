import { Navigate, Route, Routes } from 'react-router-dom';
import Layout from './components/Layout.jsx';
import RequireAuth from './components/RequireAuth.jsx';
import AssistantsPage from './pages/AssistantsPage.jsx';
import LaptopDetailPage from './pages/LaptopDetailPage.jsx';
import LaptopsPage from './pages/LaptopsPage.jsx';
import LendPage from './pages/LendPage.jsx';
import LoanDetailPage from './pages/LoanDetailPage.jsx';
import LoansPage from './pages/LoansPage.jsx';
import LoginPage from './pages/LoginPage.jsx';
import NewLaptopPage from './pages/NewLaptopPage.jsx';
import OverduePage from './pages/OverduePage.jsx';
import ReportPage from './pages/ReportPage.jsx';
import StudentDetailPage from './pages/StudentDetailPage.jsx';
import StudentsPage from './pages/StudentsPage.jsx';

const ADMIN = ['admin'];

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route
        element={
          <RequireAuth>
            <Layout />
          </RequireAuth>
        }
      >
        <Route index element={<Navigate to="/laptops" replace />} />
        <Route path="/laptops" element={<LaptopsPage />} />
        <Route path="/laptops/new" element={<RequireAuth roles={ADMIN}><NewLaptopPage /></RequireAuth>} />
        <Route path="/laptops/:id" element={<LaptopDetailPage />} />
        <Route path="/students" element={<StudentsPage />} />
        <Route path="/students/:id" element={<StudentDetailPage />} />
        <Route path="/lend" element={<LendPage />} />
        <Route path="/loans" element={<LoansPage />} />
        <Route path="/loans/:id" element={<LoanDetailPage />} />
        <Route path="/overdue" element={<OverduePage />} />
        <Route path="/report" element={<RequireAuth roles={ADMIN}><ReportPage /></RequireAuth>} />
        <Route path="/assistants" element={<RequireAuth roles={ADMIN}><AssistantsPage /></RequireAuth>} />
        <Route path="*" element={<Navigate to="/laptops" replace />} />
      </Route>
    </Routes>
  );
}
