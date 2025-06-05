import React, { FC, useContext, ReactNode } from 'react';
import { Navigate } from 'react-router-dom';
import { UserContext } from '../App';

interface PrivateRouteProps {
  children: ReactNode;
}

const PrivateRoute: FC<PrivateRouteProps> = ({ children }) => {
  const user = useContext(UserContext);
  
  if (!user) {
    return <Navigate to="/login" replace />;
  }
  
  return <>{children}</>;
};

export default PrivateRoute;
