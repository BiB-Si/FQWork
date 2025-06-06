import React, { createContext, FC, useEffect, useState } from 'react';
import './App.css';
import AppRouter from './components/AppRouter';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { GetUserDto } from './models/user/get-user.dto';
import AuthServce from './services/auth/AuthServce';
import { getAuthToken } from './utils/storage';
import { setAxiosAuthToken } from './config/axios';


export const UserContext = createContext<GetUserDto | null>(null);

const queryClient = new QueryClient();

const App: FC = () => {
  const [user, setUser] = useState<GetUserDto | null>(null);

  useEffect(() => {
    const token = getAuthToken();
    if (!token) {
      return;
    }
    
    setAxiosAuthToken(token);
    
    AuthServce.authUser().then(profile => {
      if (profile) {
        setUser(profile);
      }
    });
  }, []);

  return (
    <UserContext.Provider value={user}>
      <QueryClientProvider client={queryClient}>
        <AppRouter />
      </QueryClientProvider>
    </UserContext.Provider>
  );
};

export default App;
